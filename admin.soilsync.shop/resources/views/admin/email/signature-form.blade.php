@extends('layouts.app')

@section('title', isset($signature) ? 'Edit Signature' : 'Create Signature')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-signature mr-2"></i>
                        {{ isset($signature) ? 'Edit Signature' : 'Create Signature' }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ isset($signature) ? route('admin.email.update-signature', $signature->id) : route('admin.email.store-signature') }}"
                          method="POST">
                        @csrf
                        @if(isset($signature))
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label for="name">Signature Name:</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="{{ old('name', $signature->name ?? '') }}" required
                                   placeholder="e.g., Professional, Personal, Company">
                            @error('name')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="content">Signature Content:</label>
                            <textarea class="form-control" id="content" name="content" rows="8" required
                                      placeholder="Enter your email signature here. You can use HTML formatting.">{{ old('content', $signature->content ?? '') }}</textarea>
                            @error('content')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                You can use HTML formatting. Common tags: &lt;br&gt; for line breaks, &lt;strong&gt; for bold, &lt;em&gt; for italic.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1"
                                       {{ old('is_default', $signature->is_default ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">
                                    Set as default signature
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                The default signature will be pre-selected when composing emails.
                            </small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ isset($signature) ? 'Update' : 'Create' }} Signature
                            </button>
                            <a href="{{ route('admin.email.signatures') }}" class="btn btn-secondary ml-2">
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
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus on name field for new signatures
    @if(!isset($signature))
        document.getElementById('name').focus();
    @endif
});
</script>
@endsection