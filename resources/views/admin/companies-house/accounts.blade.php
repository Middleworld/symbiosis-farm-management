@extends('layouts.app')

@section('title', 'Accounts Helper')
@section('page-title', 'Annual Accounts Filing Helper')

@section('content')

<div class="row mb-3">
    <div class="col-md-12">
        <a href="{{ route('admin.companies-house.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Companies House
        </a>
    </div>
</div>

<div class="alert alert-warning">
    <h5><i class="fas fa-exclamation-triangle"></i> Annual Accounts are Overdue</h5>
    <p>
        <strong>Due date:</strong> {{ date('d M Y', strtotime($companyData['accounts']['next_due'])) }}<br>
        <strong>Period ending:</strong> {{ date('d M Y', strtotime($companyData['accounts']['next_made_up_to'])) }}
    </p>
    <p class="mb-0">
        As a Community Interest Company (CIC), you have additional reporting requirements beyond standard companies.
    </p>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> CIC Accounts Requirements</h5>
            </div>
            <div class="card-body">
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
                
                <div class="alert alert-warning mt-3">
                    <strong>Recommendation:</strong> Due to the complexity of CIC accounts and the overdue status, we recommend:
                    <ul class="mb-0">
                        <li>Consult with an accountant experienced with CICs</li>
                        <li>Consider requesting a filing extension (if first offense)</li>
                        <li>Ensure all CIC-specific requirements are met</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-list-check"></i> Quick Checklist</h6>
            </div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check1">
                    <label class="form-check-label" for="check1">
                        All financial records for year ending {{ date('d M Y', strtotime($companyData['accounts']['next_made_up_to'])) }} complete
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check2">
                    <label class="form-check-label" for="check2">
                        Profit & Loss statement prepared
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check3">
                    <label class="form-check-label" for="check3">
                        Balance sheet prepared
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check4">
                    <label class="form-check-label" for="check4">
                        CIC34 Community Interest Report completed
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check5">
                    <label class="form-check-label" for="check5">
                        Directors' Report prepared
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="check6">
                    <label class="form-check-label" for="check6">
                        All documents signed by directors
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="fas fa-bolt"></i> Options for Filing</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6>Option 1: Use WebFiling</h6>
                                <p class="small">File directly with Companies House if you have all documents ready</p>
                                <a href="https://ewf.companieshouse.gov.uk/" target="_blank" class="btn btn-sm btn-primary w-100">
                                    <i class="fas fa-upload"></i> Go to WebFiling
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6>Option 2: Request Extension</h6>
                                <p class="small">You may be able to request a 3-month extension</p>
                                <a href="https://find-and-update.company-information.service.gov.uk/extensions" target="_blank" class="btn btn-sm btn-warning w-100">
                                    <i class="fas fa-clock"></i> Request Extension
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card border-success">
                            <div class="card-body">
                                <h6>Option 3: Hire an Accountant (Recommended)</h6>
                                <p class="small mb-2">
                                    Given the overdue status and CIC requirements, consider professional help:
                                </p>
                                <ul class="small mb-0">
                                    <li>They can prepare accounts quickly and correctly</li>
                                    <li>Ensure CIC34 report meets all requirements</li>
                                    <li>Handle any correspondence with Companies House</li>
                                    <li>Typical cost: £300-800 for CIC accounts</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-download"></i> Generate Accounts Package</h6>
            </div>
            <div class="card-body">
                <p>Generate the complete ZIP package required for Companies House filing:</p>
                <ul>
                    <li><strong>accounts.pdf</strong> - Micro-entity accounts</li>
                    <li><strong>cicreport.pdf</strong> - CIC34 Community Interest Report</li>
                    <li><strong>manifest.xml</strong> - Package manifest</li>
                </ul>
                
                <form method="POST" action="{{ route('admin.companies-house.generate-accounts') }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <label for="period_end" class="form-label">Period End Date</label>
                            <input type="date" class="form-control" id="period_end" name="period_end" 
                                   value="{{ date('Y-m-d', strtotime($companyData['accounts']['next_made_up_to'])) }}">
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-archive"></i> Generate ZIP Package
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
                    <!-- Accounts PDF Preview -->
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
        <h1>MIDDLE WORLD FARMS C.I.C.</h1>
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
        <p>Director: TAYLOR, Martin Robert</p>
        <p>Date: ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</p>
    </div>
</body>
</html>') }}" style="width: 100%; height: 400px; border: 1px solid #dee2e6;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- CIC Report PDF Preview -->
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
        <h1>MIDDLE WORLD FARMS C.I.C.</h1>
        <h2>CIC34 Community Interest Report</h2>
        <p>For the year ended ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</p>
    </div>
    
    <div class="section">
        <h3>1. Description of Activities</h3>
        <p>Middle World Farms C.I.C. operates as a community interest company focused on:</p>
        <ul>
            <li>Urban farming and community gardening initiatives</li>
            <li>Educational programs about sustainable agriculture</li>
            <li>Local food production and distribution</li>
            <li>Community engagement and skill-sharing workshops</li>
        </ul>
    </div>
    
    <div class="section">
        <h3>2. Consultation with Stakeholders</h3>
        <div class="question">Have you consulted with your stakeholders about your activities?</div>
        <div class="answer">
            Yes, we regularly consult with local community members, customers, and other stakeholders through:
            <ul>
                <li>Community meetings and workshops</li>
                <li>Customer feedback surveys</li>
                <li>Social media engagement</li>
                <li>Partnerships with local organizations</li>
            </ul>
        </div>
    </div>
    
    <div class="section">
        <h3>3. Activities in Furtherance of Community Interest</h3>
        <p>Our activities are designed to benefit the community by:</p>
        <ul>
            <li>Providing access to fresh, locally-grown produce</li>
            <li>Promoting sustainable farming practices</li>
            <li>Creating educational opportunities</li>
            <li>Supporting local food security initiatives</li>
        </ul>
    </div>
    
    <div class="section">
        <h3>4. Directors\' Remuneration</h3>
        <table>
            <tr><th>Director Name</th><th>Position</th><th>Remuneration (£)</th></tr>
            <tr><td>[Director Name]</td><td>[Position]</td><td>0</td></tr>
        </table>
        <p><em>Note: As a CIC limited by guarantee, directors receive no remuneration for their services.</em></p>
    </div>
    
    <div class="section">
        <h3>5. Asset Locks</h3>
        <p>The company maintains appropriate asset locks to ensure that assets are used for community benefit:</p>
        <ul>
            <li>All assets are held for the benefit of the community</li>
            <li>Profits are reinvested in community activities</li>
            <li>Asset disposal requires community benefit consideration</li>
        </ul>
    </div>
    
    <div class="section">
        <h3>6. Declaration</h3>
        <p>I confirm that the information in this report is accurate and that the company has complied with its community interest obligations.</p>
        <br><br>
        <div style="margin-top: 40px;">
            ' . (file_exists(storage_path('app/public/signatures/director_signature.png')) ? '<img src="' . asset('storage/signatures/director_signature.png') . '" alt="Director Signature" style="max-width: 200px; max-height: 100px;">' : '<p style="border-top: 1px solid #000; width: 200px; padding-top: 10px;">Director Signature</p>') . '
            <p style="margin-top: 10px;">Director: TAYLOR, Martin Robert</p>
            <p>Date: ' . date('d M Y', strtotime($companyData['accounts']['next_made_up_to'] ?? '2024-09-30')) . '</p>
        </div>
    </div>
</body>
</html>') }}" style="width: 100%; height: 400px; border: 1px solid #dee2e6;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Manifest XML Preview -->
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
        &lt;Document&gt;
            &lt;File&gt;cicreport.pdf&lt;/File&gt;
            &lt;Type&gt;application/pdf&lt;/Type&gt;
        &lt;/Document&gt;
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

@endsection
