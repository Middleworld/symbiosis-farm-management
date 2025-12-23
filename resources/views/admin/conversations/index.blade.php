@extends('layouts.admin')

@section('title', 'Conversation Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Conversation Data Management</h1>
                <div class="btn-group">
                    <a href="{{ route('admin.conversations.export-training') }}" class="btn btn-success">
                        <i class="fas fa-download"></i> Export Training Data
                    </a>
                    <button type="button" class="btn btn-warning" onclick="showPurgeModal()">
                        <i class="fas fa-trash"></i> Purge Old Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Conversations</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_conversations']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Training Entries</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['training_entries']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-brain fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Chat Messages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['chat_messages']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comment-dots fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Feedback Entries</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['feedback_entries']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Search Conversations</h6>
        </div>
        <div class="card-body">
            <form id="searchForm" class="row">
                <div class="col-md-8">
                    <input type="text" class="form-control" name="q" placeholder="Search conversation content..." id="searchQuery">
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="type" id="searchType">
                        <option value="">All Types</option>
                        <option value="chat">Chat</option>
                        <option value="training">Training</option>
                        <option value="feedback">Feedback</option>
                        <option value="note">Notes</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Conversations -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Conversations</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Message Preview</th>
                            <th>User ID</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stats['recent_conversations'] as $conversation)
                        <tr>
                            <td>{{ $conversation->id }}</td>
                            <td>
                                <span class="badge badge-{{ $conversation->type === 'training' ? 'success' : ($conversation->type === 'chat' ? 'primary' : 'secondary') }}">
                                    {{ ucfirst($conversation->type) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($conversation->message, 100) }}</td>
                            <td>{{ $conversation->user_id ?? 'N/A' }}</td>
                            <td>{{ $conversation->created_at->format('M j, Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.conversations.show', $conversation->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button onclick="deleteConversation({{ $conversation->id }})" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No conversations found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Security Notice -->
<div class="alert alert-info">
    <strong><i class="fas fa-shield-alt"></i> Security Notice:</strong> 
    This conversation data is highly sensitive and protected. Access is restricted to authenticated admin users only. 
    All actions are logged for security compliance.
</div>

<script>
function showPurgeModal() {
    if (confirm('Are you sure you want to purge old conversations? This action cannot be undone.')) {
        const days = prompt('How many days to retain? (default: 365)', '365');
        if (days) {
            purgeOldConversations(parseInt(days));
        }
    }
}

function purgeOldConversations(retentionDays) {
    fetch('{{ route("admin.conversations.purge-old") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ retention_days: retentionDays })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully purged ${data.deleted_count} old conversations`);
            location.reload();
        } else {
            alert('Error purging conversations: ' + data.error);
        }
    });
}

function deleteConversation(id) {
    if (confirm('Are you sure you want to delete this conversation?')) {
        fetch(`{{ url('admin/conversations') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting conversation');
            }
        });
    }
}
</script>
@endsection
