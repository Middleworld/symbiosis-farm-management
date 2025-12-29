@extends('layouts.app')

@section('title', 'Confirmation Statement Helper')
@section('page-title', 'Confirmation Statement Filing Helper')

@section('content')

<div class="row mb-3">
    <div class="col-md-12">
        <a href="{{ route('admin.companies-house.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Companies House
        </a>
    </div>
</div>

<div class="alert alert-info">
    <h5><i class="fas fa-info-circle"></i> What is a Confirmation Statement?</h5>
    <p class="mb-0">
        A confirmation statement (previously called an annual return) confirms that the information Companies House holds about your company is correct. 
        You must file this at least once every 12 months, even if nothing has changed.
    </p>
</div>

{{-- Filing Instructions --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Filing Instructions</h5>
            </div>
            <div class="card-body">
                <ol class="mb-0">
                    <li class="mb-2">Review all information below and note any changes</li>
                    <li class="mb-2">Go to <a href="https://ewf.companieshouse.gov.uk/" target="_blank"><strong>Companies House WebFiling</strong> <i class="fas fa-external-link-alt"></i></a></li>
                    <li class="mb-2">Sign in with your GOV.UK One Login (or create one)</li>
                    <li class="mb-2">Select "File a confirmation statement"</li>
                    <li class="mb-2">Enter company number: <strong>{{ $companyData['company_number'] }}</strong></li>
                    <li class="mb-2">Follow the prompts, using the information below</li>
                    <li class="mb-2">Pay the £13 filing fee (+ any late filing penalty)</li>
                    <li>Submit!</li>
                </ol>
            </div>
        </div>
    </div>
</div>

{{-- Checklist Sections --}}
@foreach($checklist as $sectionKey => $section)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-check-circle"></i> {{ $section['title'] }}
                </h6>
            </div>
            <div class="card-body">
                @foreach($section['items'] as $item)
                <div class="mb-3 p-3 border rounded {{ $item['status'] === 'ok' ? 'border-success bg-light' : 'border-warning bg-warning bg-opacity-10' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-2">
                                @if($item['status'] === 'ok')
                                    <i class="fas fa-check-circle text-success"></i>
                                @else
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                @endif
                                {{ $item['label'] }}
                            </h6>
                            
                            <div class="mb-2">
                                <strong>Current value:</strong>
                                <div class="font-monospace bg-white p-2 rounded mt-1" style="white-space: pre-line;">{{ $item['value'] }}</div>
                            </div>
                            
                            @if(isset($item['question']))
                            <div class="alert alert-warning mb-2">
                                <strong>⚠️ Question:</strong> {{ $item['question'] }}
                            </div>
                            @endif
                            
                            @if(isset($item['note']))
                            <div class="text-muted small">
                                <i class="fas fa-info-circle"></i> {{ $item['note'] }}
                            </div>
                            @endif
                        </div>
                        <div class="ms-3">
                            <button class="btn btn-sm btn-outline-primary" onclick="copyToClipboard('{{ addslashes($item['value']) }}')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Important Reminders --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-warning">
            <div class="card-header bg-warning">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Important Reminders</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li class="mb-2">
                        <strong>Statement Date:</strong> This should be the date you're filing (today: {{ date('d M Y') }}) or your anniversary date (11 April 2025)
                    </li>
                    <li class="mb-2">
                        <strong>All information must be accurate</strong> - you're confirming this is correct as of the statement date
                    </li>
                    <li class="mb-2">
                        <strong>Late filing penalty:</strong> Your statement is overdue - there may be a penalty fee in addition to the standard £13
                    </li>
                    <li class="mb-2">
                        <strong>Authentication code:</strong> You'll need your company authentication code (sent by Companies House when you registered)
                    </li>
                    <li class="mb-2">
                        <strong>Filing takes about 15-20 minutes</strong> if all information is correct
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- File Now CTA --}}
<div class="row">
    <div class="col-md-12 text-center">
        <a href="https://ewf.companieshouse.gov.uk/" target="_blank" class="btn btn-lg btn-primary">
            <i class="fas fa-upload"></i> File Now on Companies House WebFiling
        </a>
        <div class="mt-2 text-muted">
            <small>Opens in new tab - have all the information above ready</small>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}
</script>

@endsection
