@extends('layouts.app')

@section('title', 'View Email')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $email->subject }}</h5>
                        <div>
                            <a href="{{ route('admin.email.compose') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-reply"></i> Reply
                            </a>
                            <button class="btn btn-outline-secondary btn-sm ml-2" onclick="goBack()">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="email-header mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>From:</strong> {{ $email->sender }}<br>
                                <strong>To:</strong> {{ $email->to_email }}<br>
                                @if($email->cc_email)
                                    <strong>CC:</strong> {{ $email->cc_email }}<br>
                                @endif
                            </div>
                            <div class="col-md-6 text-right">
                                <strong>Date:</strong> {{ $email->received_at ? $email->received_at->format('M j, Y H:i') : 'Unknown' }}<br>
                                @if($email->attachments && count($email->attachments) > 0)
                                    <strong>Attachments:</strong> {{ count($email->attachments) }} file(s)
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="email-body">
                        {!! nl2br(e($email->body)) !!}
                    </div>

                    @if($email->attachments && count($email->attachments) > 0)
                        <hr>
                        <div class="email-attachments">
                            <h6>Attachments:</h6>
                            <div class="row">
                                @foreach($email->attachments as $attachment)
                                    <div class="col-md-4 mb-2">
                                        <div class="card">
                                            <div class="card-body p-2">
                                                <i class="fas fa-file"></i>
                                                {{ $attachment['filename'] }}
                                                <small class="text-muted">
                                                    ({{ number_format($attachment['size'] / 1024, 1) }} KB)
                                                </small>
                                                <a href="#" class="btn btn-sm btn-outline-primary float-right">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function goBack() {
    window.history.back();
}
</script>
@endsection