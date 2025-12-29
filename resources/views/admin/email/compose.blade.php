@extends('layouts.app')

@section('title', 'Compose Email')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit mr-2"></i>Compose Email
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.email.send') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="to">To:</label>
                            <input type="email" class="form-control" id="to" name="to" required
                                   placeholder="recipient@example.com" value="{{ $preFillTo }}">
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject:</label>
                            <input type="text" class="form-control" id="subject" name="subject" required
                                   placeholder="Email subject">
                        </div>

                        <div class="form-group">
                            <label for="body">Message:</label>
                            <textarea class="form-control" id="body" name="body" rows="15" required
                                      placeholder="Type your message here..."></textarea>
                        </div>

                        <div class="form-group">
                            <label for="signature_id">Email Signature:</label>
                            <select class="form-control" id="signature_id" name="signature_id">
                                <option value="">No signature</option>
                                @foreach($signatures as $signature)
                                    <option value="{{ $signature->id }}" {{ $signature->is_default ? 'selected' : '' }}>
                                        {{ $signature->name }}
                                        @if($signature->is_default)
                                            <em>(Default)</em>
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                Choose a signature to append to your email. The default signature is pre-selected.
                                <a href="{{ route('admin.email.signatures') }}" target="_blank">Manage signatures</a>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="attachments">Attachments:</label>
                            <input type="file" class="form-control-file" id="attachments" name="attachments[]" multiple>
                            <small class="form-text text-muted">You can attach multiple files (max 10MB each)</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Email
                            </button>
                            <a href="{{ route('admin.email.index') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add some basic email composition features
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus on subject field
    document.getElementById('to').focus();
});
</script>
@endsection