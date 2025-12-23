@extends('layouts.app')

@section('title', 'Create Admin User')
@section('page-title', 'Create New Admin User')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Add New Admin User</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.admin-users.store') }}" method="POST">
                    @csrf
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Note:</strong> This will create accounts in three systems:
                        <ul class="mb-0 mt-2">
                            <li>Admin Panel (this system)</li>
                            <li>WordPress (administrator role)</li>
                            <li>FarmOS (farm manager role)</li>
                        </ul>
                        All will use the same username and password for single sign-on.
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required 
                               autofocus
                               placeholder="e.g., John Smith">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required
                               placeholder="e.g., john@middleworldfarms.org">
                        <small class="text-muted">This will be used for admin panel and FarmOS login</small>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="wordpress_email" class="form-label">WordPress Email (Optional)</label>
                        <input type="email" 
                               class="form-control @error('wordpress_email') is-invalid @enderror" 
                               id="wordpress_email" 
                               name="wordpress_email" 
                               value="{{ old('wordpress_email') }}"
                               placeholder="Leave blank to use same as main email">
                        <small class="text-muted">Only fill this if WordPress email should be different from main email</small>
                        @error('wordpress_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               required
                               minlength="8">
                        <small class="text-muted">Minimum 8 characters. Will be used for all three systems.</small>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required
                               minlength="8">
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" 
                                id="role" 
                                name="role" 
                                required>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="super_admin" {{ old('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            <option value="pos_staff" {{ old('role') == 'pos_staff' ? 'selected' : '' }}>POS Staff</option>
                        </select>
                        <small class="text-muted">Super Admins can manage other admin users</small>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label d-block">Task Permissions <span class="text-danger">*</span></label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_admin" 
                                   name="is_admin" 
                                   value="1"
                                   {{ old('is_admin') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_admin">
                                Can handle Admin Tasks
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_webdev" 
                                   name="is_webdev" 
                                   value="1"
                                   {{ old('is_webdev') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_webdev">
                                Can handle Dev Tasks
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_pos_staff" 
                                   name="is_pos_staff" 
                                   value="1"
                                   {{ old('is_pos_staff') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_pos_staff">
                                Can handle POS Tasks
                            </label>
                        </div>
                        <br>
                        <small class="text-muted">Select at least one to allow task assignment</small>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.admin-users.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create Admin User
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-3 border-warning">
            <div class="card-header bg-warning">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Important Security Notes</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Passwords are stored in plain text in the config file (current system design)</li>
                    <li>The same password will be used for Admin Panel, WordPress, and FarmOS</li>
                    <li>Choose a strong, unique password</li>
                    <li>Only create accounts for trusted staff members</li>
                    <li>Super Admins have full system access including ability to create/delete users</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
