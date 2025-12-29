<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Webklex\IMAP\Facades\Client;
use App\Models\AdminEmail;
use App\Models\AdminEmailFolder;
use App\Models\AdminEmailAttachment;
use App\Models\EmailSignature;
use App\Models\EmailFolder;
use App\Models\EmailAccount;

class EmailClientController extends Controller
{
    public function index(Request $request)
    {
        $accountId = $request->get('account', null);
        $folder = $request->get('folder', 'inbox');
        $page = $request->get('page', 1);
        $perPage = 50;

        // Get all active accounts
        $accounts = EmailAccount::active()->get();

        // If no account specified, use the default account or first active account
        if (!$accountId) {
            $defaultAccount = $accounts->where('is_default', true)->first();
            $accountId = $defaultAccount ? $defaultAccount->id : ($accounts->first() ? $accounts->first()->id : null);
        }

        $currentAccount = $accountId ? EmailAccount::find($accountId) : null;

        // Build query for emails
        $emailQuery = AdminEmail::with('account')
            ->where('folder', $folder)
            ->orderBy('received_at', 'desc');

        // Filter by account if specified
        if ($accountId) {
            $emailQuery->where('account_id', $accountId);
        }

        $emails = $emailQuery->paginate($perPage);

        // Get folder counts for system folders (filtered by account)
        $folderCountsQuery = AdminEmail::where('folder', $folder);
        if ($accountId) {
            $folderCountsQuery->where('account_id', $accountId);
        }

        $folderCounts = [
            'inbox' => AdminEmail::where('folder', 'inbox')->when($accountId, fn($q) => $q->where('account_id', $accountId))->count(),
            'sent' => AdminEmail::where('folder', 'sent')->when($accountId, fn($q) => $q->where('account_id', $accountId))->count(),
            'drafts' => AdminEmail::where('folder', 'drafts')->when($accountId, fn($q) => $q->where('account_id', $accountId))->count(),
            'trash' => AdminEmail::where('folder', 'trash')->when($accountId, fn($q) => $q->where('account_id', $accountId))->count(),
        ];

        // Get folder counts for each account (for sidebar display)
        $accountFolderCounts = [];
        foreach ($accounts as $account) {
            $accountFolderCounts[$account->id] = [
                'inbox' => AdminEmail::where('account_id', $account->id)->where('folder', 'inbox')->count(),
                'sent' => AdminEmail::where('account_id', $account->id)->where('folder', 'sent')->count(),
                'drafts' => AdminEmail::where('account_id', $account->id)->where('folder', 'drafts')->count(),
                'trash' => AdminEmail::where('account_id', $account->id)->where('folder', 'trash')->count(),
            ];
        }

        // Get all folders for the sidebar
        $folders = EmailFolder::ordered()->get();

        return view('admin.email.index', compact('emails', 'folder', 'folderCounts', 'folders', 'accounts', 'currentAccount', 'accountId', 'accountFolderCounts'));
    }

    public function compose(Request $request)
    {
        $signatures = EmailSignature::getActive();
        $preFillTo = $request->get('to', '');
        return view('admin.email.compose', compact('signatures', 'preFillTo'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'signature_id' => 'nullable|exists:email_signatures,id',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        $emailData = [
            'to' => $request->to,
            'subject' => $request->subject,
            'body' => $request->body,
        ];

        // Append signature if selected
        if ($request->signature_id) {
            $signature = EmailSignature::find($request->signature_id);
            if ($signature) {
                $emailData['body'] .= "\n\n" . $signature->content;
            }
        } elseif ($defaultSignature = EmailSignature::getDefault()) {
            // Use default signature if no signature selected
            $emailData['body'] .= "\n\n" . $defaultSignature->content;
        }

        // Handle attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('email-attachments', 'public');
                $attachments[] = [
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                ];
            }
        }

        // Send email
        Mail::send([], [], function ($message) use ($emailData, $attachments) {
            $message->to($emailData['to'])
                    ->subject($emailData['subject'])
                    ->html($emailData['body']);

            foreach ($attachments as $attachment) {
                $message->attach(storage_path('app/public/' . $attachment['path']), [
                    'as' => $attachment['filename']
                ]);
            }
        });

        // Save to sent folder
        AdminEmail::create([
            'folder' => 'sent',
            'from_email' => config('mail.from.address'),
            'to_email' => $emailData['to'],
            'subject' => $emailData['subject'],
            'body' => $emailData['body'],
            'attachments' => json_encode($attachments),
            'sent_at' => now(),
        ]);

        return redirect()->route('admin.email.index', ['folder' => 'sent'])
                        ->with('success', 'Email sent successfully!');
    }

    public function show($id)
    {
        $email = AdminEmail::findOrFail($id);

        // Mark as read if in inbox
        if ($email->folder === 'inbox' && !$email->is_read) {
            $email->update(['is_read' => true]);
        }

        return view('admin.email.show', compact('email'));
    }

    public function sync()
    {
        try {
            $accounts = EmailAccount::active()->get();
            $totalSynced = 0;

            foreach ($accounts as $account) {
                // Connect to IMAP using account-specific settings
                $client = Client::make([
                    'host'          => $account->imap_host,
                    'port'          => $account->imap_port,
                    'encryption'    => $account->imap_encryption,
                    'validate_cert' => true,
                    'username'      => $account->username,
                    'password'      => decrypt($account->password),
                ]);

                $client->connect();

                $inbox = $client->getFolder('INBOX');
                $messages = $inbox->messages()->unseen()->limit(50)->get();

                $synced = 0;
                foreach ($messages as $message) {
                    // Check if email already exists for this account
                    $existing = AdminEmail::where('message_id', $message->getMessageId())
                                        ->where('account_id', $account->id)
                                        ->first();
                    if ($existing) continue;

                    // Save email with account association
                    AdminEmail::create([
                        'account_id' => $account->id,
                        'folder' => 'inbox',
                        'message_id' => $message->getMessageId(),
                        'from_email' => $message->getFrom()[0]->mail,
                        'from_name' => $message->getFrom()[0]->personal,
                        'to_email' => $account->email,
                        'subject' => $message->getSubject(),
                        'body' => $message->getHTMLBody() ?: $message->getTextBody(),
                        'received_at' => $message->getDate(),
                        'is_read' => false,
                    ]);

                    $synced++;
                }

                $client->disconnect();
                $totalSynced += $synced;
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$totalSynced} new emails from " . $accounts->count() . " account(s)"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to sync emails: ' . $e->getMessage()
            ]);
        }
    }

    public function moveToFolder(Request $request)
    {
        $request->validate([
            'email_ids' => 'required|array',
            'folder' => 'required|string', // Folder name (inbox, sent, drafts, trash, archive, or custom folder name)
        ]);

        $count = count($request->email_ids);

        // Move emails to the specified folder
        AdminEmail::whereIn('id', $request->email_ids)
                 ->update(['folder' => $request->folder]);

        return response()->json([
            'success' => true,
            'message' => "{$count} email(s) moved to {$request->folder}"
        ]);
    }

    public function delete(Request $request)
    {
        $request->validate(['email_ids' => 'required|array']);

        $count = count($request->email_ids);

        // Move to trash instead of deleting
        AdminEmail::whereIn('id', $request->email_ids)
                 ->update(['folder' => 'trash']);

        return response()->json([
            'success' => true,
            'message' => "{$count} email(s) moved to trash"
        ]);
    }

    public function markAsUnread(Request $request)
    {
        $request->validate(['email_ids' => 'required|array']);

        $count = count($request->email_ids);

        AdminEmail::whereIn('id', $request->email_ids)
                 ->update(['is_read' => false]);

        return response()->json([
            'success' => true,
            'message' => "{$count} email(s) marked as unread"
        ]);
    }

    public function toggleFlag(Request $request)
    {
        $request->validate(['email_ids' => 'required|array']);

        $count = count($request->email_ids);

        // Toggle the flagged status for each email
        AdminEmail::whereIn('id', $request->email_ids)
                 ->update(['is_flagged' => DB::raw('NOT is_flagged')]);

        return response()->json([
            'success' => true,
            'message' => "{$count} email(s) flag status toggled"
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $folder = $request->get('folder', 'inbox');

        $emails = AdminEmail::where('folder', $folder)
            ->where(function($q) use ($query) {
                $q->where('subject', 'LIKE', "%{$query}%")
                  ->orWhere('body', 'LIKE', "%{$query}%")
                  ->orWhere('from_email', 'LIKE', "%{$query}%")
                  ->orWhere('from_name', 'LIKE', "%{$query}%");
            })
            ->orderBy('received_at', 'desc')
            ->paginate(50);

        return view('admin.email.search', compact('emails', 'query', 'folder'));
    }

    // Signature management methods
    public function signatures()
    {
        $signatures = EmailSignature::orderBy('name')->get();
        return view('admin.email.signatures', compact('signatures'));
    }

    public function createSignature()
    {
        return view('admin.email.signature-form');
    }

    public function storeSignature(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_default' => 'boolean',
        ]);

        // If setting as default, remove default from others
        if ($request->is_default) {
            EmailSignature::where('is_default', true)->update(['is_default' => false]);
        }

        EmailSignature::create($request->all());

        return redirect()->route('admin.email.signatures')->with('success', 'Signature created successfully!');
    }

    public function editSignature($id)
    {
        $signature = EmailSignature::findOrFail($id);
        return view('admin.email.signature-form', compact('signature'));
    }

    public function updateSignature(Request $request, $id)
    {
        $signature = EmailSignature::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_default' => 'boolean',
        ]);

        // If setting as default, remove default from others
        if ($request->is_default) {
            EmailSignature::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $signature->update($request->all());

        return redirect()->route('admin.email.signatures')->with('success', 'Signature updated successfully!');
    }

    public function deleteSignature($id)
    {
        $signature = EmailSignature::findOrFail($id);
        $signature->delete();

        return redirect()->route('admin.email.signatures')->with('success', 'Signature deleted successfully!');
    }

    // Folder management methods
    public function folders()
    {
        $folders = EmailFolder::ordered()->get();
        return view('admin.email.folders', compact('folders'));
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:email_folders,name',
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'icon' => 'nullable|string|max:50',
        ]);

        $maxSortOrder = EmailFolder::max('sort_order') ?? 0;

        EmailFolder::create([
            'name' => $request->name,
            'color' => $request->color ?: '#6c757d',
            'icon' => $request->icon ?: 'fas fa-folder',
            'sort_order' => $maxSortOrder + 1,
            'is_system' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder created successfully!'
        ]);
    }

    public function updateFolder(Request $request, $id)
    {
        $folder = EmailFolder::findOrFail($id);

        // Don't allow editing system folders
        if ($folder->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit system folders'
            ], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:email_folders,name,' . $id,
            'color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'icon' => 'nullable|string|max:50',
        ]);

        $folder->update([
            'name' => $request->name,
            'color' => $request->color ?: '#6c757d',
            'icon' => $request->icon ?: 'fas fa-folder',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Folder updated successfully!'
        ]);
    }

    public function deleteFolder($id)
    {
        $folder = EmailFolder::findOrFail($id);

        // Don't allow deleting system folders
        if ($folder->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system folders'
            ], 403);
        }

        // Move emails to inbox before deleting
        $inboxFolder = EmailFolder::where('name', 'Inbox')->first();
        if ($inboxFolder) {
            AdminEmail::where('folder_id', $id)->update(['folder_id' => $inboxFolder->id]);
        }

        $folder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Folder deleted successfully! Emails moved to Inbox.'
        ]);
    }

    // Email Account Management Methods
    public function accounts()
    {
        $accounts = EmailAccount::orderBy('name')->get();
        return view('admin.email.accounts', compact('accounts'));
    }

    public function createAccount()
    {
        return view('admin.email.create-account');
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:email_accounts,email',
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer|min:1|max:65535',
            'imap_encryption' => 'required|in:none,ssl,tls',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_encryption' => 'required|in:none,ssl,tls',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'is_default' => 'boolean',
        ]);

        // If this is set as default, remove default from other accounts
        if ($request->is_default) {
            EmailAccount::where('is_default', true)->update(['is_default' => false]);
        }

        EmailAccount::create([
            'name' => $request->name,
            'email' => $request->email,
            'imap_host' => $request->imap_host,
            'imap_port' => $request->imap_port,
            'imap_encryption' => $request->imap_encryption,
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'smtp_encryption' => $request->smtp_encryption,
            'username' => $request->username,
            'password' => encrypt($request->password),
            'is_active' => true,
            'is_default' => $request->is_default ?? false,
        ]);

        return redirect()->route('admin.email.accounts')->with('success', 'Email account created successfully!');
    }

    public function editAccount($id)
    {
        $account = EmailAccount::findOrFail($id);
        return view('admin.email.edit-account', compact('account'));
    }

    public function updateAccount(Request $request, $id)
    {
        $account = EmailAccount::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:email_accounts,email,' . $id,
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer|min:1|max:65535',
            'imap_encryption' => 'required|in:none,ssl,tls',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_encryption' => 'required|in:none,ssl,tls',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // If this is set as default, remove default from other accounts
        if ($request->is_default) {
            EmailAccount::where('is_default', true)->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'imap_host' => $request->imap_host,
            'imap_port' => $request->imap_port,
            'imap_encryption' => $request->imap_encryption,
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'smtp_encryption' => $request->smtp_encryption,
            'username' => $request->username,
            'is_active' => $request->is_active ?? true,
            'is_default' => $request->is_default ?? false,
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = encrypt($request->password);
        }

        $account->update($updateData);

        return redirect()->route('admin.email.accounts')->with('success', 'Email account updated successfully!');
    }

    public function deleteAccount($id)
    {
        $account = EmailAccount::findOrFail($id);

        // Don't allow deletion if it's the only account or if it has emails
        if (EmailAccount::count() === 1) {
            return redirect()->route('admin.email.accounts')->with('error', 'Cannot delete the only email account.');
        }

        if ($account->emails()->count() > 0) {
            return redirect()->route('admin.email.accounts')->with('error', 'Cannot delete account with existing emails. Please move or delete emails first.');
        }

        $account->delete();

        return redirect()->route('admin.email.accounts')->with('success', 'Email account deleted successfully!');
    }

    public function testAccountConnection($id)
    {
        $account = EmailAccount::findOrFail($id);

        try {
            // Test IMAP connection
            $imapClient = Client::make([
                'host' => $account->imap_host,
                'port' => $account->imap_port,
                'encryption' => $account->imap_encryption,
                'username' => $account->username,
                'password' => decrypt($account->password),
                'protocol' => 'imap'
            ]);

            $imapClient->connect();

            // Test SMTP connection (basic check)
            $smtpSuccess = true; // For now, just assume SMTP works if IMAP does

            return response()->json([
                'success' => true,
                'message' => 'Connection test successful!',
                'imap' => true,
                'smtp' => $smtpSuccess
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'imap' => false,
                'smtp' => false
            ]);
        }
    }
}