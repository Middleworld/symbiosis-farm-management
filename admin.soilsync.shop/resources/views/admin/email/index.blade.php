@extends('layouts.app')

@section('title', 'Email Client')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope mr-2"></i>Email Client
                    </h5>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary btn-block mb-3" onclick="composeEmail()">
                        <i class="fas fa-plus"></i> Compose
                    </button>

                    <!-- Email Accounts -->
                    @if($accounts->count() > 0)
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">Email Accounts</h6>
                            @foreach($accounts as $account)
                                <div class="account-section mb-2">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <button class="btn btn-link btn-sm p-0 text-left flex-grow-1" data-toggle="collapse" data-target="#account-{{ $account->id }}" aria-expanded="{{ $currentAccount && $currentAccount->id == $account->id ? 'true' : 'false' }}">
                                            <i class="fas fa-envelope mr-1"></i>
                                            <strong>{{ $account->display_name }}</strong>
                                            @if($account->is_default)
                                                <small class="text-primary">(Default)</small>
                                            @endif
                                        </button>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link p-0" type="button" data-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="{{ route('admin.email.edit-account', $account->id) }}">
                                                    <i class="fas fa-edit mr-2"></i>Edit Account
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="testConnection({{ $account->id }})">
                                                    <i class="fas fa-plug mr-2"></i>Test Connection
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="#" onclick="deleteAccount({{ $account->id }})">
                                                    <i class="fas fa-trash mr-2"></i>Delete Account
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="account-{{ $account->id }}" class="collapse {{ $currentAccount && $currentAccount->id == $account->id ? 'show' : '' }}">
                                        <div class="list-group ml-3">
                                            <!-- System Folders for this account -->
                                            @foreach($folders->where('is_system', true) as $systemFolder)
                                                <a href="{{ route('admin.email.index', ['account' => $account->id, 'folder' => strtolower($systemFolder->name)]) }}"
                                                   class="list-group-item list-group-item-action {{ ($currentAccount && $currentAccount->id == $account->id && request('folder') === strtolower($systemFolder->name)) ? 'active' : '' }}">
                                                    <i class="{{ $systemFolder->icon }}" style="color: {{ $systemFolder->color }}"></i> {{ $systemFolder->name }}
                                                    @if(isset($accountFolderCounts[$account->id][strtolower($systemFolder->name)]) && $accountFolderCounts[$account->id][strtolower($systemFolder->name)] > 0)
                                                        <span class="badge badge-primary badge-pill float-right">{{ $accountFolderCounts[$account->id][strtolower($systemFolder->name)] }}</span>
                                                    @endif
                                                </a>
                                            @endforeach

                                            <!-- Custom Folders for this account -->
                                            @if($folders->where('is_system', false)->count() > 0)
                                                <hr class="my-2">
                                                <h6 class="text-muted px-3 mb-2">Custom Folders</h6>
                                                @foreach($folders->where('is_system', false) as $customFolder)
                                                    <a href="{{ route('admin.email.index', ['account' => $account->id, 'folder' => $customFolder->id]) }}"
                                                       class="list-group-item list-group-item-action {{ ($currentAccount && $currentAccount->id == $account->id && $currentFolder && $currentFolder->id == $customFolder->id) ? 'active' : '' }}">
                                                        <i class="{{ $customFolder->icon }}" style="color: {{ $customFolder->color }}"></i> {{ $customFolder->name }}
                                                    </a>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No email accounts configured.
                            <a href="{{ route('admin.email.create-account') }}" class="alert-link">Add your first account</a>
                        </div>
                    @endif

                    <hr>

                    <div class="mb-3">
                        <a href="{{ route('admin.email.folders') }}" class="btn btn-outline-secondary btn-block btn-sm">
                            <i class="fas fa-folder-plus"></i> Manage Folders
                        </a>
                        <a href="{{ route('admin.email.accounts') }}" class="btn btn-outline-secondary btn-block btn-sm mt-1">
                            <i class="fas fa-cog"></i> Manage Accounts
                        </a>
                    </div>

                    <hr>

                    <button class="btn btn-outline-secondary btn-block" onclick="syncEmails()">
                        <i class="fas fa-sync"></i> Sync Emails
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ ucfirst($folder) }}</h5>
                        <div>
                            <input type="text" class="form-control form-control-sm d-inline-block w-auto" placeholder="Search emails..." id="searchInput">
                            <button class="btn btn-sm btn-outline-secondary ml-2" onclick="searchEmails()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($emails->count() > 0)
                        <!-- Bulk Actions Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-3" id="bulkActions" style="display: none;">
                            <div class="d-flex align-items-center">
                                <div class="form-check mr-3">
                                    <input class="form-check-input" type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()">
                                    <label class="form-check-label" for="selectAllCheckbox">
                                        Select All
                                    </label>
                                </div>
                                <span id="selectedCount">0</span> emails selected
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-warning mr-2" onclick="markAsUnread()">
                                    <i class="fas fa-envelope"></i> Mark Unread
                                </button>
                                <button class="btn btn-sm btn-outline-info mr-2" onclick="toggleFlag()">
                                    <i class="fas fa-flag"></i> Flag
                                </button>
                                <button class="btn btn-sm btn-outline-danger mr-2" onclick="bulkDelete()">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <button class="btn btn-sm btn-outline-primary mr-2" onclick="bulkMove()">
                                    <i class="fas fa-folder"></i> Move
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>

                        <div class="email-list">
                            @foreach($emails as $email)
                                <div class="email-item {{ $email->is_read ? '' : 'unread' }} p-3 border-bottom"
                                     onclick="viewEmail({{ $email->id }})" style="cursor: pointer;">
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex align-items-center mr-3">
                                            <input type="checkbox" class="email-checkbox mr-3" value="{{ $email->id }}"
                                                   onclick="event.stopPropagation(); toggleSelection(this)">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center">
                                                @if($email->is_starred)
                                                    <i class="fas fa-star text-warning mr-2"></i>
                                                @else
                                                    <i class="far fa-star text-muted mr-2"></i>
                                                @endif
                                                <strong>{{ $email->sender }}</strong>
                                                <small class="text-muted ml-2">{{ $email->formatted_received_at }}</small>
                                            </div>
                                            <div class="mt-1">
                                                <strong>{{ $email->subject }}</strong>
                                            </div>
                                            <div class="text-muted small mt-1">
                                                {{ $email->preview }}
                                            </div>
                                        </div>
                                        <div class="text-right ml-3">
                                            @if($email->attachments && count($email->attachments) > 0)
                                                <i class="fas fa-paperclip text-muted"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            {{ $emails->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-envelope-open text-muted" style="font-size: 3rem;"></i>
                            <h5 class="text-muted mt-3">No emails in {{ $folder }}</h5>
                            <p class="text-muted">Emails will appear here when you receive them.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function composeEmail() {
    window.location.href = '{{ route("admin.email.compose") }}';
}

function viewEmail(emailId) {
    window.location.href = '{{ route("admin.email.show", ":id") }}'.replace(':id', emailId);
}

function toggleSelection(checkbox) {
    updateBulkActions();
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.email-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });

    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.email-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.email-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');

    if (checkedCheckboxes.length > 0) {
        bulkActions.style.display = 'flex';
        selectedCount.textContent = checkedCheckboxes.length;
    } else {
        bulkActions.style.display = 'none';
    }

    // Update select all checkbox state
    if (checkboxes.length > 0 && checkedCheckboxes.length === checkboxes.length) {
        selectAllCheckbox.checked = true;
        selectAllCheckbox.indeterminate = false;
    } else if (checkedCheckboxes.length > 0) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = true;
    } else {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.email-checkbox:checked');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateBulkActions();
}

function bulkDelete() {
    const checkboxes = document.querySelectorAll('.email-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select emails to delete.');
        return;
    }

    if (!confirm(`Are you sure you want to delete ${checkboxes.length} email(s)?`)) {
        return;
    }

    const emailIds = Array.from(checkboxes).map(cb => cb.value);

    fetch('{{ route("admin.email.delete") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            email_ids: emailIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete emails'));
        }
    })
    .catch(error => {
        alert('Delete failed: ' + error);
    });
}

function bulkMove() {
    const checkboxes = document.querySelectorAll('.email-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select emails to move.');
        return;
    }

    // Simple prompt for testing
    const folder = prompt('Enter destination folder (inbox, sent, drafts, trash, archive):');
    if (!folder || !['inbox', 'sent', 'drafts', 'trash', 'archive'].includes(folder.toLowerCase())) {
        alert('Invalid folder. Please enter: inbox, sent, drafts, trash, or archive');
        return;
    }

    const emailIds = Array.from(checkboxes).map(cb => cb.value);

    fetch('{{ route("admin.email.move") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            email_ids: emailIds,
            folder: folder.toLowerCase()
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to move emails'));
        }
    })
    .catch(error => {
        console.error('Move failed:', error);
        alert('Move failed: ' + error);
    });
}

function confirmMove() {
    const folderId = document.getElementById('moveFolderSelect').value;
    if (!folderId) {
        alert('Please select a destination folder.');
        return;
    }

    const emailIds = Array.from(window.selectedEmailsForMove).map(cb => cb.value);

    fetch('{{ route("admin.email.move") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            email_ids: emailIds,
            folder: folderId
        })
    })
    .then(response => response.json())
    .then(data => {
        $('#moveModal').modal('hide');
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to move emails'));
        }
    })
    .catch(error => {
        $('#moveModal').modal('hide');
        alert('Move failed: ' + error);
    });
}

function syncEmails() {
    fetch('{{ route("admin.email.sync") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Synced ' + data.message);
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        alert('Sync failed: ' + error);
    });
}

function searchEmails() {
    const query = document.getElementById('searchInput').value;
    if (query.trim()) {
        window.location.href = '{{ route("admin.email.search") }}?q=' + encodeURIComponent(query) + '&folder={{ $folder }}';
    }
}

function markAsUnread() {
    const checkboxes = document.querySelectorAll('.email-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select emails to mark as unread.');
        return;
    }

    const emailIds = Array.from(checkboxes).map(cb => cb.value);

    fetch('{{ route("admin.email.mark-unread") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            email_ids: emailIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to mark emails as unread'));
        }
    })
    .catch(error => {
        alert('Mark as unread failed: ' + error);
    });
}

function toggleFlag() {
    const checkboxes = document.querySelectorAll('.email-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select emails to flag/unflag.');
        return;
    }

    const emailIds = Array.from(checkboxes).map(cb => cb.value);

    fetch('{{ route("admin.email.toggle-flag") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            email_ids: emailIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to toggle flag status'));
        }
    })
    .catch(error => {
        alert('Toggle flag failed: ' + error);
    });
}

function testConnection(accountId) {
    fetch('{{ route("admin.email.test-account", ":id") }}'.replace(':id', accountId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Connection test successful!\nIMAP: ' + (data.imap ? '✓' : '✗') + '\nSMTP: ' + (data.smtp ? '✓' : '✗'));
        } else {
            alert('Connection test failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Connection test failed: ' + error);
    });
}

function deleteAccount(accountId) {
    if (!confirm('Are you sure you want to delete this email account? This action cannot be undone and will delete all associated emails.')) {
        return;
    }

    fetch('{{ route("admin.email.delete-account", ":id") }}'.replace(':id', accountId), {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete account'));
        }
    })
    .catch(error => {
        alert('Delete failed: ' + error);
    });
}
</script>

<style>
.email-item:hover {
    background-color: #f8f9fa;
}

.email-item.unread {
    background-color: #e3f2fd;
    border-left: 3px solid #2196f3;
}

.email-item.unread:hover {
    background-color: #bbdefb;
}

/* Make checkboxes larger */
.email-checkbox {
    transform: scale(1.5);
    margin-right: 12px !important;
}
</style>
@endsection