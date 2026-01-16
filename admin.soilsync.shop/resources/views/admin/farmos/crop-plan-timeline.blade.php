@extends('layouts.app')

@section('title', 'Crop Plan Timeline - farmOS Integration')

@section('page-title', 'Crop Plan Timeline')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center w-100 gap-3">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary btn-sm" id="refreshData">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div class="text-center flex-grow-1">
            <p class="lead mb-0">Interactive crop plan timeline visualization</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="planSelect" class="mb-0 text-nowrap small fw-bold">Plan:</label>
                <select class="form-select form-select-sm" id="planSelect" style="width: 200px;">
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ $defaultPlan && $defaultPlan->id == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.farmos.dashboard') }}">
                    <i class="fas fa-tractor"></i> farmOS
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="fas fa-chart-line"></i> Crop Plan Timeline
            </li>
        </ol>
    </nav>

    @if(isset($error))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ $error }}
        </div>
    @else
        <!-- Timeline Chart Iframe -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-gantt text-primary me-2"></i>
                            Crop Plan Timeline Chart
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <iframe src="/admin/farmos/crop-plans/gantt-chart?plan_id={{ $defaultPlan ? $defaultPlan->id : ($plans->first()->id ?? 1) }}"
                                class="w-100 border-0"
                                style="height: 800px; min-height: 600px;"
                                frameborder="0">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Plan selector functionality
  document.getElementById('planSelect').addEventListener('change', function() {
    const planId = this.value;
    const iframe = document.querySelector('iframe');
    if (iframe) {
      iframe.src = `/admin/farmos/crop-plans/gantt-chart?plan_id=${planId}`;
    }
  });
});
</script>
@endpush