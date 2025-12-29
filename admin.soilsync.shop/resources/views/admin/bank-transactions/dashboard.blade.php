@extends('layouts.admin')

@section('title', 'Accounting & Companies House Dashboard')

@push('head')
<meta name="format-detection" content="telephone=no">
<style>
.month-row {
    cursor: pointer;
    transition: background-color 0.2s;
}
.month-row:hover {
    background-color: #f8f9fa;
}
.month-chevron {
    transition: transform 0.3s;
}
.month-chevron.rotated {
    transform: rotate(180deg);
}
.transaction-item {
    border-bottom: 1px solid #e9ecef;
    padding: 0.5rem 0;
}
.transaction-item:last-child {
    border-bottom: none;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Accounting & Companies House Dashboard</h1>
                    <p class="text-muted mb-0">Financial Year {{ $from->format('M Y') }} - {{ $to->format('M Y') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <!-- Year Filter Dropdown -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $yearLabels[$year] ?? "Year $year" }}
                        </button>
                        <ul class="dropdown-menu">
                            @foreach($availableYears as $y)
                            <li>
                                <a class="dropdown-item {{ $y == $year ? 'active' : '' }}" 
                                   href="{{ route('admin.bank-transactions.dashboard', ['year' => $y]) }}">
                                    {{ $yearLabels[$y] ?? "Year $y" }} (Oct {{ $y }} to Sep {{ $y + 1 }})
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    <a href="{{ route('admin.bank-transactions.import-form') }}" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Import CSV
                    </a>
                    <a href="{{ route('admin.bank-transactions.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-list-ul me-2"></i>View Transactions
                    </a>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-success text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50 mb-1">Total Income</h6>
                                    <h3 class="mb-0">£{{ number_format($stats['total_income'], 2) }}</h3>
                                </div>
                                <i class="bi bi-arrow-up-circle display-4 text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50 mb-1">Total Expenses</h6>
                                    <h3 class="mb-0">£{{ number_format($stats['total_expenses'], 2) }}</h3>
                                </div>
                                <i class="bi bi-arrow-down-circle display-4 text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card {{ $stats['net_profit'] >= 0 ? 'bg-primary' : 'bg-warning' }} text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white-50 mb-1">Net Profit</h6>
                                    <h3 class="mb-0">£{{ number_format($stats['net_profit'], 2) }}</h3>
                                </div>
                                <i class="bi bi-{{ $stats['net_profit'] >= 0 ? 'graph-up' : 'graph-down' }} display-4 text-white-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Chart Placeholder -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Monthly Income vs Expenses</h5>
                        </div>
                        <div class="card-body">
                            @if($monthlyData->isEmpty())
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    No transactions found for {{ $year }}. 
                                    <a href="{{ route('admin.bank-transactions.import-form') }}" class="alert-link">Import your bank CSV</a> to see charts!
                                </div>
                            @else
                                <canvas id="monthlyChart" height="80"></canvas>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Breakdown Table -->
            @if(!$monthlyData->isEmpty())
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Monthly Breakdown (Click to View Details)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th class="text-end">Income</th>
                                            <th class="text-end">Expenses</th>
                                            <th class="text-end">Net</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $months = ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
                                        $monthNumbers = [10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9];
                                        @endphp
                                        
                                        @foreach($monthNumbers as $index => $monthNum)
                                            @php
                                            $monthRecord = $monthlyData->firstWhere('month', $monthNum);
                                            $income = $monthRecord ? $monthRecord->income : 0;
                                            $expenses = $monthRecord ? $monthRecord->expenses : 0;
                                            $net = $income - $expenses;
                                            $displayYear = $monthNum >= 10 ? $year : $year + 1;
                                            @endphp
                                            <tr class="month-row cursor-pointer" 
                                                data-month="{{ $monthNum }}" 
                                                data-year="{{ $displayYear }}"
                                                onclick="toggleMonthDetails({{ $monthNum }}, {{ $displayYear }})">
                                                <td>
                                                    <strong>{{ $months[$index] }} {{ $displayYear }}</strong>
                                                    <i class="bi bi-chevron-down month-chevron ms-2" id="chevron-{{ $monthNum }}"></i>
                                                </td>
                                                <td class="text-end text-success">£{{ number_format($income, 2) }}</td>
                                                <td class="text-end text-danger">£{{ number_format($expenses, 2) }}</td>
                                                <td class="text-end {{ $net >= 0 ? 'text-success' : 'text-warning' }} fw-bold">
                                                    £{{ number_format($net, 2) }}
                                                </td>
                                                <td class="text-center">
                                                    @if($net < 0)
                                                        <span class="badge bg-warning">⚠️ Loss</span>
                                                    @else
                                                        <span class="badge bg-success">✓ Profit</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr class="month-details-row d-none" id="details-{{ $monthNum }}">
                                                <td colspan="5">
                                                    <div class="p-3 bg-light">
                                                        <div class="d-flex justify-content-center mb-3">
                                                            <div class="spinner-border text-primary" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Category Breakdown -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Income by Category</h5>
                        </div>
                        <div class="card-body">
                            @if($incomeByCategory->isEmpty())
                                <p class="text-muted">No income transactions yet.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tbody>
                                            @foreach($incomeByCategory as $item)
                                                <tr>
                                                    <td>{{ $item->category ?? 'Uncategorized' }}</td>
                                                    <td class="text-end fw-bold text-success">£{{ number_format($item->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-cart-dash me-2"></i>Expenses by Category</h5>
                        </div>
                        <div class="card-body">
                            @if($expensesByCategory->isEmpty())
                                <p class="text-muted">No expense transactions yet.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tbody>
                                            @foreach($expensesByCategory as $item)
                                                <tr>
                                                    <td>{{ $item->category ?? 'Uncategorized' }}</td>
                                                    <td class="text-end fw-bold text-danger">£{{ number_format($item->total, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Companies House Accounts Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Companies House Accounts Filing</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger mb-3">
                        <h6><i class="fas fa-exclamation-triangle"></i> Annual Accounts are Overdue</h6>
                        <p>
                            <strong>Due date:</strong> {{ date('d M Y', strtotime($companyData['accounts']['next_due'])) }}<br>
                            <strong>Period ending:</strong> {{ date('d M Y', strtotime($companyData['accounts']['next_made_up_to'])) }}
                        </p>
                        <p class="mb-0">
                            As a Community Interest Company (CIC), you have additional reporting requirements beyond standard companies.
                        </p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6>Generate Accounts Package</h6>
                                    <p class="small">Create properly structured ZIP package for Companies House filing with required directory structure</p>
                                    <form method="POST" action="{{ route('admin.companies-house.generate-accounts') }}" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="year" value="{{ $year }}">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="period_end" class="form-label small">Period End Date</label>
                                                <input type="date" class="form-control form-control-sm" id="period_end" name="period_end" 
                                                       value="{{ $to->format('Y-m-d') }}" readonly>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-file-archive"></i> Generate ZIP
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> Files include print instructions for PDF conversion
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6>Filing Options</h6>
                                    <div class="d-grid gap-2">
                                        <a href="https://www.gov.uk/file-your-company-annual-accounts" target="_blank" class="btn btn-sm btn-primary">
                                            <i class="fas fa-upload"></i> File Accounts Guide
                                        </a>
                                        <a href="https://find-and-update.company-information.service.gov.uk/extensions" target="_blank" class="btn btn-sm btn-warning">
                                            <i class="fas fa-clock"></i> Request Extension
                                        </a>
                                    </div>
                                    <p class="small mt-2 mb-0">
                                        <strong>Penalty Status:</strong> Up to £1,500 if over 6 months late
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="accordion" id="accountsPreviewAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accountsPreview">
                                        <i class="fas fa-file-pdf me-2"></i> accounts.pdf - Micro-entity Accounts
                                    </button>
                                </h2>
                                <div id="accountsPreview" class="accordion-collapse collapse show" data-bs-parent="#accountsPreviewAccordion">
                                    <div class="accordion-body">
                                        <div class="alert alert-success mb-3">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>PDF Files:</strong> These documents will be generated as PDF files ready for Companies House submission.
                                        </div>
                                        <div class="border p-3" style="background: #f8f9fa;">
                                            <iframe src="data:text/html;charset=utf-8;base64,{{ base64_encode('<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Micro-entity Accounts - ' . $to->format('d M Y') . '</title>
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
        <p>For the year ended ' . $to->format('d M Y') . '</p>
    </div>
    
    <h3>Balance Sheet</h3>
    <table>
        <tr><th>Assets</th><th>£</th></tr>
        <tr><td>Current Assets</td><td>' . number_format($stats['total_income'], 2) . '</td></tr>
        <tr><td>Total Assets</td><td>' . number_format($stats['total_income'], 2) . '</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Liabilities</th><th>£</th></tr>
        <tr><td>Current Liabilities</td><td>' . number_format($stats['total_expenses'], 2) . '</td></tr>
        <tr><td>Total Liabilities</td><td>' . number_format($stats['total_expenses'], 2) . '</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Net Assets</th><th>' . number_format($stats['net_profit'], 2) . '</th></tr>
    </table>
    
    <h3>Profit and Loss Account</h3>
    <table>
        <tr><th>Income</th><th>£</th></tr>
        <tr><td>Turnover</td><td>' . number_format($stats['total_income'], 2) . '</td></tr>
        <tr><td>Other Income</td><td>0.00</td></tr>
        <tr><td>Total Income</td><td>' . number_format($stats['total_income'], 2) . '</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Expenditure</th><th>£</th></tr>
        <tr><td>Cost of Sales</td><td>0.00</td></tr>
        <tr><td>Administrative Expenses</td><td>' . number_format($stats['total_expenses'], 2) . '</td></tr>
        <tr><td>Total Expenditure</td><td>' . number_format($stats['total_expenses'], 2) . '</td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Net Profit/Loss</th><th>' . number_format($stats['net_profit'], 2) . '</th></tr>
    </table>
    
    <div class="signature">
        ' . (file_exists(storage_path('app/public/signatures/director_signature.png')) ? '<img src="' . asset('storage/signatures/director_signature.png') . '" alt="Director Signature" style="max-width: 200px; max-height: 100px; margin-bottom: 10px;">' : '') . '
        <p>Director: TAYLOR, Martin Robert</p>
        <p>Date: ' . $to->format('d M Y') . '</p>
    </div>
</body>
</html>') }}" style="width: 100%; height: 300px; border: 1px solid #dee2e6;"></iframe>
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
                                <div id="manifestPreview" class="accordion-collapse collapse" data-bs-parent="#accountsPreviewAccordion">
                                    <div class="accordion-body">
                                        <pre class="border p-3" style="background: #f8f9fa; font-size: 12px; overflow-x: auto;"><code>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;manifest xmlns="http://www.govtalk.gov.uk/CM/envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.govtalk.gov.uk/CM/envelope http://xmlgw.companieshouse.gov.uk/v1-0/schema/CHIPS_manifest-v1-0.xsd"&gt;
    &lt;manifest-data&gt;
        &lt;package-identifier&gt;13617115&lt;/package-identifier&gt;
        &lt;package-type&gt;CIC34&lt;/package-type&gt;
        &lt;package-version&gt;1.0&lt;/package-version&gt;
        &lt;submission-date&gt;{{ date('Y-m-d') }}&lt;/submission-date&gt;
        &lt;submission-time&gt;{{ date('H:i:s') }}&lt;/submission-time&gt;
        &lt;supplier&gt;
            &lt;supplier-name&gt;Middleworld Farms Ltd&lt;/supplier-name&gt;
            &lt;supplier-id&gt;13617115&lt;/supplier-id&gt;
        &lt;/supplier&gt;
    &lt;/manifest-data&gt;
    &lt;files&gt;
        &lt;file&gt;
            &lt;file-name&gt;CIC-13617115/CIC34/accounts/accounts.html&lt;/file-name&gt;
            &lt;file-type&gt;text/html&lt;/file-type&gt;
            &lt;file-size&gt;1024&lt;/file-size&gt;
        &lt;/file&gt;
        &lt;file&gt;
            &lt;file-name&gt;CIC-13617115/cicreport.pdf&lt;/file-name&gt;
            &lt;file-type&gt;application/pdf&lt;/file-type&gt;
            &lt;file-size&gt;1024&lt;/file-size&gt;
        &lt;/file&gt;
        &lt;file&gt;
            &lt;file-name&gt;CIC-13617115/manifest.xml&lt;/file-name&gt;
            &lt;file-type&gt;text/xml&lt;/file-type&gt;
            &lt;file-size&gt;1024&lt;/file-size&gt;
        &lt;/file&gt;
    &lt;/files&gt;
&lt;/manifest&gt;</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$monthlyData->isEmpty())
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyData = @json($monthlyData);
    
    // Financial year months: Oct, Nov, Dec, Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep
    const allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const labels = monthlyData.map(d => allMonths[d.month - 1]);
    const income = monthlyData.map(d => parseFloat(d.income));
    const expenses = monthlyData.map(d => parseFloat(d.expenses));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Income',
                    data: income,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Expenses',
                    data: expenses,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '£' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': £' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endif

<script>
// Month details toggle
let loadedMonths = {};

function toggleMonthDetails(month, year) {
    const detailsRow = document.getElementById(`details-${month}`);
    const chevron = document.getElementById(`chevron-${month}`);
    
    if (detailsRow.classList.contains('d-none')) {
        // Open details
        detailsRow.classList.remove('d-none');
        chevron.classList.add('rotated');
        
        // Load data if not already loaded
        if (!loadedMonths[month]) {
            loadMonthTransactions(month, year);
            loadedMonths[month] = true;
        }
    } else {
        // Close details
        detailsRow.classList.add('d-none');
        chevron.classList.remove('rotated');
    }
}

function loadMonthTransactions(month, year) {
    const detailsRow = document.getElementById(`details-${month}`);
    const detailsCell = detailsRow.querySelector('td');
    
    fetch(`/admin/bank-transactions/month-details?month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                detailsCell.innerHTML = renderMonthDetails(data.data, month, year);
            } else {
                detailsCell.innerHTML = `<div class="alert alert-warning">${data.message || 'Failed to load transactions'}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading month details:', error);
            detailsCell.innerHTML = '<div class="alert alert-danger">Error loading transactions. Please try again.</div>';
        });
}

function renderMonthDetails(data, month, year) {
    const months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    
    let html = `
        <div class="p-3 bg-light">
            <h6 class="mb-3">${months[month]} ${year} - Detailed Transactions</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <strong>Income (£${parseFloat(data.income_total).toFixed(2)})</strong>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
    `;
    
    if (data.income.length === 0) {
        html += '<p class="text-muted">No income transactions</p>';
    } else {
        data.income.forEach(tx => {
            html += `
                <div class="transaction-item">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${tx.description || 'Transaction'}</strong>
                            <br><small class="text-muted">${tx.transaction_date} | ${tx.category || 'Uncategorized'}</small>
                        </div>
                        <div class="text-end text-success">
                            <strong>£${parseFloat(tx.amount).toFixed(2)}</strong>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += `
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-danger text-white">
                            <strong>Expenses (£${parseFloat(data.expenses_total).toFixed(2)})</strong>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
    `;
    
    if (data.expenses.length === 0) {
        html += '<p class="text-muted">No expense transactions</p>';
    } else {
        data.expenses.forEach(tx => {
            html += `
                <div class="transaction-item">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>${tx.description || 'Transaction'}</strong>
                            <br><small class="text-muted">${tx.transaction_date} | ${tx.category || 'Uncategorized'}</small>
                        </div>
                        <div class="text-end text-danger">
                            <strong>£${parseFloat(tx.amount).toFixed(2)}</strong>
                        </div>
                    </div>
                </div>
            `;
        });
    }
    
    html += `
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <strong>Summary:</strong> ${data.income.length} income transaction(s), ${data.expenses.length} expense transaction(s)
                        | <strong>Net:</strong> <span class="${parseFloat(data.income_total) - parseFloat(data.expenses_total) >= 0 ? 'text-success' : 'text-warning'}">£${(parseFloat(data.income_total) - parseFloat(data.expenses_total)).toFixed(2)}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return html;
}

// Force dropdown to work with click handler
document.addEventListener('DOMContentLoaded', function() {
    const dropdownButton = document.querySelector('[data-bs-toggle="dropdown"]');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (dropdownButton && dropdownMenu) {
        console.log('Setting up manual dropdown');
        
        dropdownButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle show class
            const isShown = dropdownMenu.classList.contains('show');
            dropdownMenu.classList.toggle('show');
            dropdownButton.setAttribute('aria-expanded', !isShown);
            
            console.log('Dropdown toggled:', !isShown);
        });
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!dropdownButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.classList.remove('show');
                dropdownButton.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>
@endsection
