@extends('layouts.app')

@section('title', 'Email Folders')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-folder mr-2"></i>Email Folders
                    </h5>
                    <div class="card-tools">
                        <button class="btn btn-primary btn-sm" onclick="showCreateFolderModal()">
                            <i class="fas fa-plus"></i> Create Folder
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row">
                        @foreach($folders as $folder)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card folder-card {{ $folder->is_system ? 'border-primary' : 'border-secondary' }}"
                                     style="border-left: 4px solid {{ $folder->color }};">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="card-title mb-1">
                                                    <i class="{{ $folder->icon }}" style="color: {{ $folder->color }};"></i>
                                                    {{ $folder->name }}
                                                    @if($folder->is_system)
                                                        <small class="badge badge-primary">System</small>
                                                    @endif
                                                </h6>
                                                <p class="card-text small text-muted mb-2">
                                                    {{ $folder->total_count }} emails
                                                    @if($folder->unread_count > 0)
                                                        <span class="badge badge-danger">{{ $folder->unread_count }} unread</span>
                                                    @endif
                                                </p>
                                            </div>
                                            @if(!$folder->is_system)
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <button class="dropdown-item" onclick="editFolder({{ $folder->id }}, '{{ $folder->name }}', '{{ $folder->color }}', '{{ $folder->icon }}')">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <button class="dropdown-item text-danger" onclick="deleteFolder({{ $folder->id }}, '{{ $folder->name }}')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <a href="{{ route('admin.email.index', ['folder' => strtolower($folder->name)]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View Emails
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Folder Modal -->
<div class="modal fade" id="folderModal" tabindex="-1" role="dialog" aria-labelledby="folderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="folderModalLabel">Create Folder</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="folderForm">
                <div class="modal-body">
                    <input type="hidden" id="folderId" name="folder_id">

                    <div class="form-group">
                        <label for="folderName">Folder Name</label>
                        <input type="text" class="form-control" id="folderName" name="name" required
                               placeholder="Enter folder name">
                    </div>

                    <div class="form-group">
                        <label for="folderColor">Color</label>
                        <input type="color" class="form-control" id="folderColor" name="color" value="#6c757d">
                    </div>

                    <div class="form-group">
                        <label for="folderIcon">Icon (FontAwesome class)</label>
                        <input type="text" class="form-control" id="folderIcon" name="icon" value="fas fa-folder"
                               placeholder="fas fa-folder">
                        <small class="form-text text-muted">
                            Choose from <a href="https://fontawesome.com/icons" target="_blank">FontAwesome icons</a>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Folder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateFolderModal() {
    document.getElementById('folderModalLabel').textContent = 'Create Folder';
    document.getElementById('folderForm').reset();
    document.getElementById('folderId').value = '';
    $('#folderModal').modal('show');
}

function editFolder(id, name, color, icon) {
    document.getElementById('folderModalLabel').textContent = 'Edit Folder';
    document.getElementById('folderId').value = id;
    document.getElementById('folderName').value = name;
    document.getElementById('folderColor').value = color;
    document.getElementById('folderIcon').value = icon;
    $('#folderModal').modal('show');
}

function deleteFolder(id, name) {
    if (confirm(`Are you sure you want to delete the "${name}" folder? All emails will be moved to Inbox.`)) {
        fetch(`{{ url('/admin/email/folders') }}/${id}`, {
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
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Delete failed: ' + error);
        });
    }
}

document.getElementById('folderForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const folderId = formData.get('folder_id');
    const isEdit = folderId !== '';

    const url = isEdit
        ? `{{ url('/admin/email/folders') }}/${folderId}`
        : '{{ route("admin.email.create-folder") }}';

    const method = isEdit ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#folderModal').modal('hide');
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Save failed: ' + error);
    });
});
</script>

<style>
.folder-card {
    transition: transform 0.2s;
}

.folder-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
@endsection