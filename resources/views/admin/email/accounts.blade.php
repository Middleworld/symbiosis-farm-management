@extends('layouts.app')

@section('title', 'Email Accounts')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope mr-2"></i>Email Accounts
                        </h5>
                        <a href="{{ route('admin.email.create-account') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Account
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($accounts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>IMAP Server</th>
                                        <th>SMTP Server</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accounts as $account)
                                        <tr>
                                            <td>
                                                <strong>{{ $account->display_name }}</strong>
                                                @if($account->is_default)
                                                    <br><small class="text-primary"><i class="fas fa-star"></i> Default Account</small>
                                                @endif
                                            </td>
                                            <td>{{ $account->email }}</td>
                                            <td>
                                                {{ $account->imap_host }}:{{ $account->imap_port }}
                                                <br><small class="text-muted">{{ strtoupper($account->imap_encryption) }}</small>
                                            </td>
                                            <td>
                                                {{ $account->smtp_host }}:{{ $account->smtp_port }}
                                                <br><small class="text-muted">{{ strtoupper($account->smtp_encryption) }}</small>
                                            </td>
                                            <td>
                                                @if($account->is_active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-info" onclick="testConnection({{ $account->id }})" title="Test Connection">
                                                        <i class="fas fa-plug"></i>
                                                    </button>
                                                    <a href="{{ route('admin.email.edit-account', $account->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if($accounts->count() > 1)
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteAccount({{ $account->id }})" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-envelope fa-3x text-muted mb-3"></i>
                            <h4>No Email Accounts</h4>
                            <p class="text-muted">You haven't configured any email accounts yet.</p>
                            <a href="{{ route('admin.email.create-account') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Your First Account
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function testConnection(accountId) {
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;

    fetch('{{ route("admin.email.test-account", ":id") }}'.replace(':id', accountId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        button.innerHTML = originalHtml;
        button.disabled = false;

        if (data.success) {
            alert('Connection test successful!\nIMAP: ' + (data.imap ? '✓' : '✗') + '\nSMTP: ' + (data.smtp ? '✓' : '✗'));
        } else {
            alert('Connection test failed: ' + data.message);
        }
    })
    .catch(error => {
        button.innerHTML = originalHtml;
        button.disabled = false;
        alert('Connection test failed: ' + error);
    });
}

function deleteAccount(accountId) {
    if (!confirm('Are you sure you want to delete this email account? This action cannot be undone and will delete all associated emails.')) {
        return;
    }

    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;

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
        button.innerHTML = originalHtml;
        button.disabled = false;
        alert('Delete failed: ' + error);
    });
}
</script>
@endsection