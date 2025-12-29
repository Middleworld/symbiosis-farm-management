@extends('layouts.app')

@section('title', '{{ $title }} - MWF Admin')
@section('page-title', '{{ $title }}')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-hammer fa-3x text-muted"></i>
                </div>
                <h3 class="mb-3">{{ $title }}</h3>
                <p class="lead text-muted">{{ $description }}</p>
                
                <div class="alert alert-info mt-4">
                    <strong><i class="fas fa-info-circle me-2"></i>Coming Soon</strong><br>
                    This feature is currently under development and will be available in a future update.
                </div>
                
                <div class="mt-4">
                    <a href="/admin" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
