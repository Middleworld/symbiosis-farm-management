@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-bolt"></i> farmOS Quick Forms (Embedded)
                    </h4>
                </div>
                <div class="card-body">
                    <p class="lead">
                        Record farm activities directly from the admin interface using farmOS quick forms.
                    </p>
                    
                    <div class="alert alert-info">
                        <strong>🔬 Embedding Test</strong> - These forms are loaded from farmOS in an iframe. 
                        Session authentication should work automatically.
                    </div>
                    
                    <div class="row g-3 mt-3">
                        @foreach($forms as $slug => $form)
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ route('admin.farmos.quickforms.embed', $slug) }}" 
                               class="text-decoration-none">
                                <div class="card h-100 border-primary hover-card">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-{{ $form['icon'] }} fa-3x text-primary"></i>
                                        </div>
                                        <h5 class="card-title">{{ $form['title'] }}</h5>
                                        <p class="small text-muted mb-0">
                                            {{ $form['path'] }}
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5>Custom farmOS Page</h5>
                    <form method="GET" action="{{ route('admin.farmos.quickforms.custom') }}" class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">farmOS Path</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ config('farmos.url') }}/</span>
                                <input type="text" 
                                       name="path" 
                                       class="form-control" 
                                       placeholder="log/add/seeding"
                                       required>
                            </div>
                            <small class="form-text text-muted">
                                Examples: log/add/seeding, asset/add/plant, plan/add/crop
                            </small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block w-100">
                                <i class="fas fa-external-link-alt"></i> Embed Page
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-card {
    transition: all 0.3s ease;
    cursor: pointer;
}
.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
</style>
@endsection
