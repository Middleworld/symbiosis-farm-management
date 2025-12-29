@extends('layouts.admin')

@section('title', 'Import Bank Transactions')

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Import Bank Transactions</h1>
                <a href="{{ route('admin.bank-transactions.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Transactions
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Upload CSV File</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.bank-transactions.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="mb-4">
                                    <label for="csv_file" class="form-label fw-bold">
                                        Select CSV File <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" 
                                           class="form-control @error('csv_file') is-invalid @enderror" 
                                           id="csv_file" 
                                           name="csv_file" 
                                           accept=".csv,.txt"
                                           required>
                                    @error('csv_file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Export CSV from your online banking. Maximum file size: 10MB
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="auto_categorize" 
                                               name="auto_categorize" 
                                               value="1" 
                                               checked>
                                        <label class="form-check-label" for="auto_categorize">
                                            <strong>Auto-categorize transactions</strong>
                                            <small class="d-block text-muted">
                                                Automatically assign categories based on transaction descriptions
                                            </small>
                                        </label>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-cloud-upload me-2"></i>Import Transactions
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-lightbulb me-2"></i>CSV Format Guide</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold">Expected Columns:</h6>
                            <ul class="list-unstyled mb-3">
                                <li><i class="bi bi-check-circle text-success me-2"></i>Date / Transaction Date</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Description / Memo</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Debit / Money Out</li>
                                <li><i class="bi bi-check-circle text-success me-2"></i>Credit / Money In</li>
                                <li><i class="bi bi-check-circle text-muted me-2"></i>Balance (optional)</li>
                                <li><i class="bi bi-check-circle text-muted me-2"></i>Reference (optional)</li>
                            </ul>

                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-shield-check me-2"></i>
                                <strong>Duplicate Detection</strong><br>
                                <small>Transactions with the same date, description, and amount will be automatically skipped.</small>
                            </div>

                            <h6 class="fw-bold">Auto-Categorization:</h6>
                            <small class="text-muted">
                                Transactions are categorized based on keywords:
                                <ul class="mt-2 mb-0 small">
                                    <li><strong>Income:</strong> Stripe, payment, vegbox</li>
                                    <li><strong>Supplies:</strong> seed, compost, nursery</li>
                                    <li><strong>Staff:</strong> wages, payroll, PAYE</li>
                                    <li><strong>Utilities:</strong> electric, water, fuel</li>
                                    <li>...and more!</li>
                                </ul>
                            </small>
                        </div>
                    </div>

                    <div class="card shadow-sm border-success mt-3">
                        <div class="card-body">
                            <h6 class="fw-bold text-success">
                                <i class="bi bi-piggy-bank me-2"></i>£0 Cost Forever!
                            </h6>
                            <p class="mb-0 small text-muted">
                                No enterprise APIs, no hidden fees, no vendor lock-in. 
                                Just simple CSV imports that work with any UK bank. 🚜🥬
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
