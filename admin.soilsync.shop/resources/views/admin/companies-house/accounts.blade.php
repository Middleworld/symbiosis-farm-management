@extends('layouts.app')

@section('title', 'Accounts Helper')
@section('page-title', 'Annual Accounts Filing Helper')

@section('styles')
<style>
.companies-house-accounts {
    width: 100%;
    overflow-x: hidden;
    padding-bottom: 2rem;
    position: relative;
    z-index: 2;
}

.companies-house-accounts .card {
    margin-bottom: 1rem;
    word-wrap: break-word;
}

.companies-house-accounts .container-fluid {
    width: 100%;
}

.main-content {
    position: relative !important;
    z-index: 1050 !important;
    background: white;
    min-height: 100vh !important;
    overflow-y: auto;
    overflow-x: hidden;
}

@media (max-width: 768px) {
    .companies-house-accounts .container-fluid {
        width: 100vw !important;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    .main-content {
        width: 100vw !important;
        margin-left: 0 !important;
    }
}
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3 companies-house-accounts">
<div class="row mb-3">
    <div class="col-md-12">
        <a href="{{ route('admin.companies-house.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Companies House
        </a>
    </div>
</div>

@if(!$isApiConfigured)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Companies House API Not Configured</h6>
            </div>
            <div class="card-body">
                <p>Companies House API access is not configured. Add your API key in Settings → Company &amp; Farm.</p>
                <p class="mb-0">Until configured, the system will use fallback data for demonstration purposes.</p>
            </div>
        </div>
    </div>
</div>
@elseif($isOAuthConfigured && !$isAuthenticated)
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="fas fa-key"></i> Companies House Authentication Required</h6>
            </div>
            <div class="card-body">
                <p>OAuth2 is configured but not authenticated. Connect to access OAuth-only features.</p>
                <a href="{{ route('admin.companies-house.authorize') }}" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Connect to Companies House
                </a>
            </div>
        </div>
    </div>
</div>
@else
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-check-circle"></i> Connected to Companies House</h6>
            </div>
            <div class="card-body">
                <p class="mb-0">Successfully authenticated with Companies House. You can now access live company data and use filing features.</p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> 
                    @if($isCic)
                        CIC Filing Challenge
                    @else
                        Company Accounts Filing
                    @endif
                </h6>
            </div>
            <div class="card-body">
                @if($isCic)
                    <p><strong>You're right - most accounting software doesn't support CIC filing!</strong></p>
                    <p>Community Interest Companies have unique requirements that standard accounting packages don't handle:</p>
                    <ul>
                        <li>CIC34 Community Interest Report (required annually)</li>
                        <li>Asset lock declarations</li>
                        <li>Community benefit reporting</li>
                        <li>Director remuneration restrictions</li>
                    </ul>
                    <p class="mb-0">That's why we built this packaged filing system - to bridge the gap between your accounting data and Companies House requirements.</p>
                @else
                    <p><strong>Standard company accounts filing for {{ ucfirst(str_replace('_', ' ', $companyType)) }}</strong></p>
                    <p>Your company type requires standard annual accounts filing with Companies House. This system generates the required package format for easy submission.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle"></i> Annual Accounts are Overdue</h5>
            <p>
                <strong>Due date:</strong> {{ date('d M Y', strtotime($companyData['accounts']['next_due'])) }}<br>
                <strong>Period ending:</strong> {{ date('d M Y', strtotime($companyData['accounts']['next_made_up_to'])) }}
            </p>
            <p class="mb-0">
                @if($isCic)
                    As a Community Interest Company (CIC), you have additional reporting requirements beyond standard companies.
                @else
                    As a {{ ucfirst(str_replace('_', ' ', $companyType)) }}, you must file your annual accounts with Companies House.
                @endif
            </p>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> 
                    @if($isCic)
                        CIC Accounts Requirements
                    @else
                        Company Accounts Requirements
                    @endif
                </h5>
            </div>
            <div class="card-body">
                @if($isCic)
                    <p>As a CIC, you must file:</p>
                    <ol>
                        <li class="mb-2">
                            <strong>Full annual accounts</strong> (including profit & loss, balance sheet)
                        </li>
                        <li class="mb-2">
                            <strong>CIC34 Report</strong> (Community Interest Company report)
                        </li>
                        <li class="mb-2">
                            <strong>Directors' Report</strong>
                        </li>
                    </ol>
                    <p class="mb-0">Recommendation: Due to the complexity of CIC accounts and the overdue status, we recommend:</p>
                    <ul class="mb-0">
                        <li>Consult with an accountant experienced with CICs</li>
                        <li>Consider requesting a filing extension (if first offense)</li>
                        <li>Ensure all CIC-specific requirements are met</li>
                    </ul>
                @else
                    <p>As a {{ ucfirst(str_replace('_', ' ', $companyType)) }}, you must file:</p>
                    <ol>
                        <li class="mb-2">
                            <strong>Full annual accounts</strong> (including profit & loss, balance sheet)
                        </li>
                        <li class="mb-2">
                            <strong>Directors' Report</strong>
                        </li>
                    </ol>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-cogs"></i> Packaged Filing Options</h6>
            </div>
            <div class="card-body">
                <p><strong>Automated Package Generation</strong></p>
                <p>Generate ready-to-upload ZIP packages from your transaction data:</p>
                <ul>
                    <li><strong>Simple ZIP:</strong> PDFs + manifest (easy upload)</li>
                    <li><strong>iXBRL Format:</strong> Structured for automated filing</li>
                    @if($isCic)
                        <li><strong>CIC34 Report:</strong> Automatically generated</li>
                    @endif
                    <li><strong>Manifest XML:</strong> Companies House compliant</li>
                </ul>
                <form method="POST" action="{{ route('admin.companies-house.generate-accounts') }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <label for="package_format" class="form-label">Package Format</label>
                            <select name="package_format" id="package_format" class="form-select" required>
                                <option value="simple">Simple ZIP (accounts.pdf@if($isCic) + cicreport.pdf@endif + manifest.xml)</option>
                                <option value="ixbrl">iXBRL Format (accounts.html + manifest.xml)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="period_end" class="form-label">Period End Date</label>
                            <input type="date" name="period_end" id="period_end" class="form-control" value="{{ date('Y-m-d', strtotime($companyData['accounts']['next_made_up_to'])) }}" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-download"></i> Generate Package
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="alert alert-danger">
            <h6><i class="fas fa-exclamation-triangle"></i> Current Penalty Status</h6>
            <p>Your accounts are overdue. Penalties are:</p>
            <ul class="mb-0">
                <li>Up to 1 month late: £150</li>
                <li>1-3 months late: £375</li>
                <li>3-6 months late: £750</li>
                <li>Over 6 months late: £1,500</li>
            </ul>
            <p class="mt-2 mb-0">
                <strong>Action needed ASAP to minimize penalties!</strong>
            </p>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-eye"></i> Preview Generated Files</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Below is a preview of the files that will be included in your ZIP package. These are the exact HTML documents that will be generated.</p>
                <div class="accordion" id="previewAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accountsPreview">
                                <i class="fas fa-file-pdf me-2"></i> accounts.pdf - Micro-entity Accounts
                            </button>
                        </h2>
                        <div id="accountsPreview" class="accordion-collapse collapse show" data-bs-parent="#previewAccordion">
                            <div class="accordion-body">
                                <div class="border p-3" style="background: #f8f9fa;">
                                    <iframe src="data:text/html;charset=utf-8,{{ urlencode('<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Micro-entity Accounts - ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
        h1, h2, h3 { color: #333; margin: 15px 0 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 11px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .signature { margin-top: 30px; border-top: 1px solid #000; width: 200px; padding-top: 5px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . ($companyData['company_name'] ?? 'Company Name') . '</h1>
        <h2>Micro-entity Accounts</h2>
        <p>For the year ended ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</p>
    </div>
    
    <h3>Balance Sheet</h3>
    <table>
        <tr><th>Assets</th><th>£</th></tr>
        <tr><td>Current Assets</td><td>21,834.81</td></tr>
        <tr><td>Total Assets</td><td>21,834.81</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Liabilities</th><th>£</th></tr>
        <tr><td>Current Liabilities</td><td>20,636.48</td></tr>
        <tr><td>Total Liabilities</td><td>20,636.48</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Net Assets</th><th>1,198.33</th></tr>
    </table>
    
    <h3>Profit and Loss Account</h3>
    <table>
        <tr><th>Income</th><th>£</th></tr>
        <tr><td>Turnover</td><td>21,834.81</td></tr>
        <tr><td>Other Income</td><td>0.00</td></tr>
        <tr><td>Total Income</td><td>21,834.81</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Expenditure</th><th>£</th></tr>
        <tr><td>Cost of Sales</td><td>0.00</td></tr>
        <tr><td>Administrative Expenses</td><td>20,636.48</td></tr>
        <tr><td>Total Expenditure</td><td>20,636.48</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Net Profit/Loss</th><th>1,198.33</th></tr>
    </table>
    
    <div class="signature">
        ' . (file_exists(storage_path('app/public/signatures/director_signature.png')) ? '<img src="' . asset('storage/signatures/director_signature.png') . '" alt="Director Signature" style="max-width: 200px; max-height: 100px; margin-bottom: 10px;">' : '') . '
        <p>Director: ' . ($companyData['director_name'] ?? 'Director Name') . '</p>
        <p>Date: ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</p>
    </div>
</body>
</html>') }}" style="width: 100%; height: 400px; border: 1px solid #dee2e6;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#cicPreview">
                                <i class="fas fa-file-pdf me-2"></i> cicreport.pdf - CIC34 Community Interest Report
                            </button>
                        </h2>
                        <div id="cicPreview" class="accordion-collapse collapse" data-bs-parent="#previewAccordion">
                            <div class="accordion-body">
                                <div class="border p-3" style="background: #f8f9fa;">
                                    <iframe src="data:text/html;charset=utf-8,{{ urlencode('<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CIC34 Community Interest Report - ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.4; font-size: 12px; }
        h1, h2, h3 { color: #333; margin: 15px 0 8px 0; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section { margin: 20px 0; }
        .question { margin: 10px 0; font-weight: bold; }
        .answer { margin-left: 15px; padding: 8px; background-color: #f9f9f9; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 11px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; }
        ul { margin: 5px 0; padding-left: 20px; }
        li { margin: 3px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . ($companyData['company_name'] ?? 'Company Name') . '</h1>
        <h2>CIC34 Community Interest Report</h2>
        <p>For the year ended ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</p>
    </div>
    
    <div class="section">
        <h3>1. Description of Activities</h3>
        <p>This report summarises the company’s community benefit activities and confirms compliance with CIC requirements.</p>
    </div>
    
    <div class="section">
        <h3>2. Consultation with Stakeholders</h3>
        <div class="question">Have you consulted with your stakeholders about your activities?</div>
        <div class="answer">
            Yes, we regularly consult with local community members, customers, and other stakeholders through meetings, feedback, and partnerships.
        </div>
    </div>
    
    <div class="section">
        <h3>3. Activities in Furtherance of Community Interest</h3>
        <p>Our activities are designed to benefit the community by providing access to fresh produce and promoting sustainable practices.</p>
    </div>
    
    <div class="section">
        <h3>4. Directors\' Remuneration</h3>
        <table>
            <tr><th>Director Name</th><th>Position</th><th>Remuneration (£)</th></tr>
            <tr><td>' . ($companyData['director_name'] ?? 'Director Name') . '</td><td>Director</td><td>0</td></tr>
        </table>
        <p><em>Note: As a CIC, directors receive no remuneration for their services.</em></p>
    </div>
    
    <div class="section">
        <h3>5. Asset Locks</h3>
        <p>The company maintains appropriate asset locks to ensure assets are used for community benefit.</p>
    </div>
    
    <div class="section">
        <h3>6. Declaration</h3>
        <p>I confirm that the information in this report is accurate and that the company has complied with its community interest obligations.</p>
        <br><br>
        <div style="margin-top: 40px;">
            ' . (file_exists(storage_path('app/public/signatures/director_signature.png')) ? '<img src="' . asset('storage/signatures/director_signature.png') . '" alt="Director Signature" style="max-width: 200px; max-height: 100px;">' : '<p style="border-top: 1px solid #000; width: 200px; padding-top: 10px;">Director Signature</p>') . '
            <p style="margin-top: 10px;">Director: ' . ($companyData['director_name'] ?? 'Director Name') . '</p>
            <p>Date: ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</p>
        </div>
    </div>
</body>
</html>') }}" style="width: 100%; height: 400px; border: 1px solid #dee2e6;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manifestPreview">
                                <i class="fas fa-file-code me-2"></i> manifest.xml - Package Manifest
                            </button>
                        </h2>
                        <div id="manifestPreview" class="accordion-collapse collapse" data-bs-parent="#previewAccordion">
                            <div class="accordion-body">
                                <pre class="border p-3" style="background: #f8f9fa; font-size: 12px; overflow-x: auto;"><code>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;Package xmlns="http://www.companieshouse.gov.uk/ef/ixbrl/package/0.1/"&gt;
    &lt;Contents&gt;
        &lt;Document&gt;
            &lt;File&gt;accounts.pdf&lt;/File&gt;
            &lt;Type&gt;application/pdf&lt;/Type&gt;
        &lt;/Document&gt;
        @if($isCic)
        &lt;Document&gt;
            &lt;File&gt;cicreport.pdf&lt;/File&gt;
            &lt;Type&gt;application/pdf&lt;/Type&gt;
        &lt;/Document&gt;
        @endif
    &lt;/Contents&gt;
&lt;/Package&gt;</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
@endsection
