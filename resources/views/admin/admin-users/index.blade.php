@extends('layouts.app')

@section('title', 'Admin Users')
@section('page-title', 'Admin User Management')

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <a href="{{ route('admin.admin-users.create') }}" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add New Admin User
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users-cog"></i> Admin Users</h5>
            </div>
            <div class="card-body">
                @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Permissions</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr class="{{ !$user['active'] ? 'table-secondary' : '' }}">
                                <td>
                                    <strong>{{ $user['name'] }}</strong>
                                    @if($user['email'] === Session::get('admin_user')['email'])
                                        <span class="badge bg-info ms-1">You</span>
                                    @endif
                                </td>
                                <td>{{ $user['email'] }}</td>
                                <td>
                                    @if($user['role'] === 'super_admin')
                                        <span class="badge bg-danger">Super Admin</span>
                                    @elseif($user['role'] === 'pos_staff')
                                        <span class="badge bg-warning">POS Staff</span>
                                    @else
                                        <span class="badge bg-primary">Admin</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user['is_admin'] ?? false)
                                        <span class="badge bg-success">Admin Tasks</span>
                                    @endif
                                    @if($user['is_webdev'] ?? false)
                                        <span class="badge bg-info">Dev Tasks</span>
                                    @endif
                                    @if($user['is_pos_staff'] ?? false)
                                        <span class="badge bg-warning">POS Tasks</span>
                                    @endif
                                    @if(!($user['is_admin'] ?? false) && !($user['is_webdev'] ?? false) && !($user['is_pos_staff'] ?? false))
                                        <span class="text-muted">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user['active'])
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $user['created_at'] }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.admin-users.edit', $user['index']) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($user['email'] !== Session::get('admin_user')['email'])
                                        <form action="{{ route('admin.admin-users.destroy', $user['index']) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this admin user?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-4">No admin users found.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-info-circle"></i> About Admin User Management</h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Creating a new admin user will:</strong></p>
                <ul class="mb-0">
                    <li>Add the user to this admin panel with the specified credentials</li>
                    <li>Automatically create a WordPress administrator account with the same username and password</li>
                    <li>Automatically create a FarmOS farm manager account with the same username and password</li>
                    <li>Allow single sign-on: logging into the admin panel will authenticate with both WordPress and FarmOS</li>
                </ul>
                <hr>
                <p class="mb-2"><strong>Role Types:</strong></p>
                <ul class="mb-0">
                    <li><strong>Admin:</strong> Standard admin access</li>
                    <li><strong>Super Admin:</strong> Full system access including user management</li>
                </ul>
                <hr>
                <p class="mb-2"><strong>Task Permissions:</strong></p>
                <ul class="mb-0">
                    <li><strong>Admin Tasks:</strong> Can be assigned general administrative tasks</li>
                    <li><strong>Dev Tasks:</strong> Can be assigned web development tasks</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
