@extends('layouts.app')

@section('title', 'Edit Admin User')
@section('page-title', 'Edit Admin User')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Admin User: {{ $user['name'] }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.admin-users.update', $user['index']) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" value="{{ $user['email'] }}" disabled>
                        <small class="text-muted">Email cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $user['name']) }}" 
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password (Optional)</label>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password"
                               minlength="8">
                        <small class="text-muted">Leave blank to keep current password. Minimum 8 characters if changing.</small>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation"
                               minlength="8">
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" 
                                id="role" 
                                name="role" 
                                required>
                            <option value="admin" {{ old('role', $user['role']) == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="super_admin" {{ old('role', $user['role']) == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            <option value="pos_staff" {{ old('role', $user['role']) == 'pos_staff' ? 'selected' : '' }}>POS Staff</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label d-block">Task Permissions</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_admin" 
                                   name="is_admin" 
                                   value="1"
                                   {{ old('is_admin', $user['is_admin'] ?? false) ? 'checked' : '' }}>
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
                                   {{ old('is_webdev', $user['is_webdev'] ?? false) ? 'checked' : '' }}>
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
                                   {{ old('is_pos_staff', $user['is_pos_staff'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_pos_staff">
                                Can handle POS Tasks
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="active" 
                                   name="active" 
                                   value="1"
                                   {{ old('active', $user['active'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active (user can log in)
                            </label>
                        </div>
                        <small class="text-muted">Uncheck to disable login without deleting the account</small>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.admin-users.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
