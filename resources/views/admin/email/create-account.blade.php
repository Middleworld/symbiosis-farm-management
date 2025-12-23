@extends('layouts.app')

@section('title', 'Create Email Account')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus mr-2"></i>Create Email Account
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.email.store-account') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Account Name *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                    <small class="form-text text-muted">Display name for this email account</small>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                    <small class="form-text text-muted">The email address for this account</small>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">IMAP Settings</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="imap_host">IMAP Host *</label>
                                    <input type="text" class="form-control @error('imap_host') is-invalid @enderror" id="imap_host" name="imap_host" value="{{ old('imap_host', 'imap.gmail.com') }}" required>
                                    @error('imap_host')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="imap_port">Port *</label>
                                    <input type="number" class="form-control @error('imap_port') is-invalid @enderror" id="imap_port" name="imap_port" value="{{ old('imap_port', 993) }}" required>
                                    @error('imap_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="imap_encryption">Encryption *</label>
                                    <select class="form-control @error('imap_encryption') is-invalid @enderror" id="imap_encryption" name="imap_encryption" required>
                                        <option value="ssl" {{ old('imap_encryption', 'ssl') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="tls" {{ old('imap_encryption') == 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="none" {{ old('imap_encryption') == 'none' ? 'selected' : '' }}>None</option>
                                    </select>
                                    @error('imap_encryption')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="mb-3">SMTP Settings</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="smtp_host">SMTP Host *</label>
                                    <input type="text" class="form-control @error('smtp_host') is-invalid @enderror" id="smtp_host" name="smtp_host" value="{{ old('smtp_host', 'smtp.gmail.com') }}" required>
                                    @error('smtp_host')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="smtp_port">Port *</label>
                                    <input type="number" class="form-control @error('smtp_port') is-invalid @enderror" id="smtp_port" name="smtp_port" value="{{ old('smtp_port', 587) }}" required>
                                    @error('smtp_port')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="smtp_encryption">Encryption *</label>
                                    <select class="form-control @error('smtp_encryption') is-invalid @enderror" id="smtp_encryption" name="smtp_encryption" required>
                                        <option value="tls" {{ old('smtp_encryption', 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="ssl" {{ old('smtp_encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="none" {{ old('smtp_encryption') == 'none' ? 'selected' : '' }}>None</option>
                                    </select>
                                    @error('smtp_encryption')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username *</label>
                                    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required>
                                    <small class="form-text text-muted">Usually the same as your email address</small>
                                    @error('username')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Password *</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                    <small class="form-text text-muted">For Gmail, use an App Password</small>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">
                                    Set as default account
                                </label>
                                <small class="form-text text-muted">This will be the default account for new emails and replies</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Account
                            </button>
                            <a href="{{ route('admin.email.accounts') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Auto-fill common provider settings
document.getElementById('email').addEventListener('blur', function() {
    const email = this.value;
    const domain = email.split('@')[1];

    if (domain) {
        switch(domain.toLowerCase()) {
            case 'gmail.com':
                document.getElementById('imap_host').value = 'imap.gmail.com';
                document.getElementById('imap_port').value = '993';
                document.getElementById('imap_encryption').value = 'ssl';
                document.getElementById('smtp_host').value = 'smtp.gmail.com';
                document.getElementById('smtp_port').value = '587';
                document.getElementById('smtp_encryption').value = 'tls';
                if (!document.getElementById('username').value) {
                    document.getElementById('username').value = email;
                }
                break;
            case 'outlook.com':
            case 'hotmail.com':
            case 'live.com':
                document.getElementById('imap_host').value = 'outlook.office365.com';
                document.getElementById('imap_port').value = '993';
                document.getElementById('imap_encryption').value = 'ssl';
                document.getElementById('smtp_host').value = 'smtp-mail.outlook.com';
                document.getElementById('smtp_port').value = '587';
                document.getElementById('smtp_encryption').value = 'tls';
                if (!document.getElementById('username').value) {
                    document.getElementById('username').value = email;
                }
                break;
            case 'yahoo.com':
                document.getElementById('imap_host').value = 'imap.mail.yahoo.com';
                document.getElementById('imap_port').value = '993';
                document.getElementById('imap_encryption').value = 'ssl';
                document.getElementById('smtp_host').value = 'smtp.mail.yahoo.com';
                document.getElementById('smtp_port').value = '587';
                document.getElementById('smtp_encryption').value = 'tls';
                if (!document.getElementById('username').value) {
                    document.getElementById('username').value = email;
                }
                break;
        }
    }
});
</script>
@endsection