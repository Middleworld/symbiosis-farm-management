@extends('layouts.app')

@section('title', 'Companies House')
@section('page-title', 'Companies House Management')

@section('content')

{{-- URGENT ALERTS --}}
@if($companyData['confirmation_statement']['overdue'] || $companyData['accounts']['overdue'])
<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-danger border-danger">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> URGENT ACTION REQUIRED</h4>
            
            @if($companyData['confirmation_statement']['overdue'])
            <div class="mb-2">
                <strong>Confirmation Statement Overdue</strong>
                <ul class="mb-0">
                    <li>Due date: {{ date('d M Y', strtotime($companyData['confirmation_statement']['next_due'])) }}</li>
                    <li>Status: <span class="badge bg-danger">OVERDUE</span></li>
                    <li>Late filing penalties may apply</li>
                </ul>
            </div>
            @endif
            
            @if($companyData['accounts']['overdue'])
            <div class="mb-2">
                <strong>Annual Accounts Overdue</strong>
                <ul class="mb-0">
                    <li>Due date: {{ date('d M Y', strtotime($companyData['accounts']['next_due'])) }}</li>
                    <li>Status: <span class="badge bg-danger">OVERDUE</span></li>
                    <li>Penalty: £150+ (increases with delay)</li>
                </ul>
            </div>
            @endif
            
            <hr>
            <div class="d-flex gap-2">
                <a href="https://www.gov.uk/file-your-company-annual-accounts" target="_blank" class="btn btn-danger">
                    <i class="fas fa-external-link-alt"></i> File Accounts on GOV.UK
                </a>
                <a href="{{ route('admin.companies-house.confirmation-helper') }}" class="btn btn-warning">
                    <i class="fas fa-clipboard-check"></i> Confirmation Statement Helper
                </a>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Company Overview Card --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-building"></i> Company Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Company Name:</th>
                                <td>{{ $companyData['company_name'] }}</td>
                            </tr>
                            <tr>
                                <th>Company Number:</th>
                                <td>
                                    <strong>{{ $companyData['company_number'] }}</strong>
                                    <a href="https://find-and-update.company-information.service.gov.uk/company/{{ $companyData['company_number'] }}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-external-link-alt"></i> View on Companies House
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th>Company Type:</th>
                                <td>
                                    <span class="badge bg-info">Community Interest Company (CIC)</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge bg-success">{{ ucfirst($companyData['company_status']) }}</span>
                                    <span class="badge bg-warning text-dark">⚠️ Strike Off Proposed</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Incorporated:</th>
                                <td>{{ date('d M Y', strtotime($companyData['date_of_creation'])) }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Registered Office Address</h6>
                        <address>
                            {{ $companyData['registered_office_address']['address_line_1'] }}<br>
                            @if($companyData['registered_office_address']['address_line_2'])
                                {{ $companyData['registered_office_address']['address_line_2'] }}<br>
                            @endif
                            {{ $companyData['registered_office_address']['locality'] }}<br>
                            {{ $companyData['registered_office_address']['region'] }}<br>
                            {{ $companyData['registered_office_address']['postal_code'] }}<br>
                            {{ $companyData['registered_office_address']['country'] }}
                        </address>
                    </div>
                </div>

                @if(!empty($companyData['previous_names']))
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Previous Names</h6>
                        <ul class="list-unstyled">
                            @foreach($companyData['previous_names'] as $prevName)
                            <li>
                                <span class="badge bg-secondary">{{ $prevName['name'] }}</span>
                                <small class="text-muted">(ceased {{ date('d M Y', strtotime($prevName['ceased_on'])) }})</small>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Filing Status Cards --}}
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card {{ $companyData['confirmation_statement']['overdue'] ? 'border-danger' : 'border-success' }}">
            <div class="card-header {{ $companyData['confirmation_statement']['overdue'] ? 'bg-danger text-white' : 'bg-success text-white' }}">
                <h6 class="mb-0">
                    <i class="fas fa-file-alt"></i> Confirmation Statement
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="40%">Next Due:</th>
                        <td>
                            {{ date('d M Y', strtotime($companyData['confirmation_statement']['next_due'])) }}
                            @if($companyData['confirmation_statement']['overdue'])
                                <span class="badge bg-danger ms-2">OVERDUE</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Made up to:</th>
                        <td>{{ date('d M Y', strtotime($companyData['confirmation_statement']['next_made_up_to'])) }}</td>
                    </tr>
                </table>
                
                @if($companyData['confirmation_statement']['overdue'])
                <div class="alert alert-danger mt-3 mb-0">
                    <small>
                        <strong>Action:</strong> File immediately to avoid further penalties and prevent strike off!
                    </small>
                </div>
                @endif
                
                <div class="mt-3">
                    <a href="{{ route('admin.companies-house.confirmation-helper') }}" class="btn btn-sm btn-primary w-100">
                        <i class="fas fa-clipboard-check"></i> Confirmation Statement Helper
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card {{ $companyData['accounts']['overdue'] ? 'border-danger' : 'border-success' }}">
            <div class="card-header {{ $companyData['accounts']['overdue'] ? 'bg-danger text-white' : 'bg-success text-white' }}">
                <h6 class="mb-0">
                    <i class="fas fa-calculator"></i> Annual Accounts
                </h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="40%">Next Due:</th>
                        <td>
                            {{ date('d M Y', strtotime($companyData['accounts']['next_due'])) }}
                            @if($companyData['accounts']['overdue'])
                                <span class="badge bg-danger ms-2">OVERDUE</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Period End:</th>
                        <td>{{ date('d M Y', strtotime($companyData['accounts']['next_made_up_to'])) }}</td>
                    </tr>
                </table>
                
                @if($companyData['accounts']['overdue'])
                <div class="alert alert-danger mt-3 mb-0">
                    <small>
                        <strong>Penalty:</strong> £150+ and increasing. File ASAP or request extension.
                    </small>
                </div>
                @endif
                
                <div class="mt-3">
                    <a href="{{ route('admin.companies-house.accounts-helper') }}" class="btn btn-sm btn-primary w-100">
                        <i class="fas fa-file-invoice"></i> Accounts Helper
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Nature of Business --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-briefcase"></i> Nature of Business (SIC Codes)</h6>
            </div>
            <div class="card-body">
                <div class="list-group">
                    @foreach($companyData['sic_codes'] as $code => $description)
                    <div class="list-group-item">
                        <div class="d-flex">
                            <div class="me-3">
                                <span class="badge bg-primary">{{ $code }}</span>
                            </div>
                            <div>
                                {{ $description }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="https://ewf.companieshouse.gov.uk/" target="_blank" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-upload"></i> WebFiling Portal
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="https://find-and-update.company-information.service.gov.uk/company/{{ $companyData['company_number'] }}" 
                           target="_blank" 
                           class="btn btn-outline-secondary w-100 mb-2">
                            <i class="fas fa-search"></i> View Public Record
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.companies-house.confirmation-helper') }}" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-clipboard-list"></i> Filing Checklist
                        </a>
                    </div>
                    <div class="col-md-3">
                        <button onclick="alert('Coming soon: Direct API integration')" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-sync"></i> Refresh Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
