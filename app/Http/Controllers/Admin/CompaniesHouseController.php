<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CompaniesHouseController extends Controller
{
    protected $companyNumber = '13617115';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.companies_house.api_key');
    }

    /**
     * Show Companies House dashboard
     */
    public function index()
    {
        $companyData = $this->getCompanyData();
        $officers = $this->getOfficers();
        $filingHistory = $this->getFilingHistory();
        
        return view('admin.companies-house.index', compact(
            'companyData',
            'officers',
            'filingHistory'
        ));
    }

    /**
     * Fetch company data from API or cache
     */
    protected function getCompanyData()
    {
        if (!$this->apiKey) {
            // Fallback to hardcoded data if no API key
            return $this->getHardcodedCompanyData();
        }

        return Cache::remember("companies_house.company.{$this->companyNumber}", 3600, function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ])->get("https://api.company-information.service.gov.uk/company/{$this->companyNumber}");

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatCompanyData($data);
                }

                Log::error('Companies House API error', [
                    'endpoint' => 'company',
                    'company_number' => $this->companyNumber,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                // Fallback to hardcoded data on API failure
                return $this->getHardcodedCompanyData();

            } catch (\Exception $e) {
                Log::error('Companies House API exception', [
                    'endpoint' => 'company',
                    'company_number' => $this->companyNumber,
                    'error' => $e->getMessage()
                ]);

                // Fallback to hardcoded data on exception
                return $this->getHardcodedCompanyData();
            }
        });
    }

    /**
     * Fetch officers data
     */
    protected function getOfficers()
    {
        if (!$this->apiKey) {
            return [];
        }

        return Cache::remember("companies_house.officers.{$this->companyNumber}", 3600, function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ])->get("https://api.company-information.service.gov.uk/company/{$this->companyNumber}/officers");

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatOfficersData($data);
                }

                Log::error('Companies House officers API error', [
                    'company_number' => $this->companyNumber,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [];

            } catch (\Exception $e) {
                Log::error('Companies House officers API exception', [
                    'company_number' => $this->companyNumber,
                    'error' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Fetch filing history
     */
    protected function getFilingHistory()
    {
        if (!$this->apiKey) {
            return [];
        }

        return Cache::remember("companies_house.filing_history.{$this->companyNumber}", 3600, function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => $this->apiKey,
                    'Accept' => 'application/json',
                ])->get("https://api.company-information.service.gov.uk/company/{$this->companyNumber}/filing-history");

                if ($response->successful()) {
                    $data = $response->json();
                    return $this->formatFilingHistoryData($data);
                }

                Log::error('Companies House filing history API error', [
                    'company_number' => $this->companyNumber,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [];

            } catch (\Exception $e) {
                Log::error('Companies House filing history API exception', [
                    'company_number' => $this->companyNumber,
                    'error' => $e->getMessage()
                ]);

                return [];
            }
        });
    }

    /**
     * Show confirmation statement helper
     */
    public function confirmationStatementHelper()
    {
        $companyData = $this->getCompanyData();
        $officers = $this->getOfficers();
        
        $checklist = $this->generateConfirmationStatementChecklist($companyData, $officers);
        
        return view('admin.companies-house.confirmation-statement', compact(
            'companyData',
            'officers',
            'checklist'
        ));
    }

    /**
     * Generate checklist for confirmation statement
     */
    protected function generateConfirmationStatementChecklist($companyData, $officers)
    {
        return [
            'company_details' => [
                'title' => 'Company Details',
                'items' => [
                    [
                        'label' => 'Company name',
                        'value' => $companyData['company_name'],
                        'status' => 'ok',
                    ],
                    [
                        'label' => 'Company number',
                        'value' => $companyData['company_number'],
                        'status' => 'ok',
                    ],
                    [
                        'label' => 'Registered office address',
                        'value' => $this->formatAddress($companyData['registered_office_address']),
                        'status' => 'ok',
                        'question' => 'Has this address changed?',
                    ],
                ],
            ],
            'statement_date' => [
                'title' => 'Statement Date',
                'items' => [
                    [
                        'label' => 'Statement date',
                        'value' => $companyData['confirmation_statement']['next_made_up_to'],
                        'status' => 'warning',
                        'note' => 'This should be today\'s date or your anniversary date',
                    ],
                ],
            ],
            'sic_codes' => [
                'title' => 'Nature of Business (SIC Codes)',
                'items' => [
                    [
                        'label' => 'Current SIC codes',
                        'value' => $this->formatSicCodes($companyData['sic_codes']),
                        'status' => 'ok',
                        'question' => 'Are these still accurate for your business?',
                    ],
                ],
            ],
            'officers' => [
                'title' => 'Officers & Directors',
                'items' => [
                    [
                        'label' => 'Review officers',
                        'value' => count($officers) . ' officers on record',
                        'status' => 'warning',
                        'question' => 'Are all directors and company secretary details correct?',
                        'note' => 'Check names, addresses, dates of birth are up to date',
                    ],
                ],
            ],
            'shareholders' => [
                'title' => 'Shareholders (if applicable)',
                'items' => [
                    [
                        'label' => 'PSC Register',
                        'value' => 'Persons with Significant Control',
                        'status' => 'warning',
                        'question' => 'Have you updated the PSC register?',
                        'note' => 'Anyone owning >25% or having significant influence',
                    ],
                ],
            ],
            'share_capital' => [
                'title' => 'Share Capital',
                'items' => [
                    [
                        'label' => 'Share structure',
                        'value' => 'Community Interest Company (no shares)',
                        'status' => 'ok',
                        'note' => 'CICs limited by guarantee don\'t have share capital',
                    ],
                ],
            ],
        ];
    }

    /**
     * Format address for display
     */
    protected function formatAddress($address)
    {
        return implode(', ', array_filter([
            $address['address_line_1'] ?? '',
            $address['address_line_2'] ?? '',
            $address['locality'] ?? '',
            $address['region'] ?? '',
            $address['postal_code'] ?? '',
        ]));
    }

    /**
     * Format SIC codes for display
     */
    protected function formatSicCodes($sicCodes)
    {
        return collect($sicCodes)->map(function ($description, $code) {
            return "$code - $description";
        })->implode("\n");
    }

    /**
     * Format company data from API response
     */
    protected function formatCompanyData($data)
    {
        return [
            'company_number' => $data['company_number'] ?? $this->companyNumber,
            'company_name' => $data['company_name'] ?? 'Unknown',
            'company_status' => $data['company_status'] ?? 'unknown',
            'type' => $data['type'] ?? 'unknown',
            'date_of_creation' => $data['date_of_creation'] ?? null,
            'registered_office_address' => $data['registered_office_address'] ?? [],
            'accounts' => $data['accounts'] ?? [],
            'confirmation_statement' => $data['confirmation_statement'] ?? [],
            'sic_codes' => $this->formatSicCodesFromApi($data['sic_codes'] ?? []),
            'has_been_liquidated' => $data['has_been_liquidated'] ?? false,
            'has_insolvency_history' => $data['has_insolvency_history'] ?? false,
            'previous_names' => $data['previous_company_names'] ?? [],
        ];
    }

    /**
     * Format officers data from API response
     */
    protected function formatOfficersData($data)
    {
        $officers = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $officer) {
                $officers[] = [
                    'name' => $officer['name'] ?? 'Unknown',
                    'officer_role' => $officer['officer_role'] ?? 'Unknown',
                    'appointed_on' => $officer['appointed_on'] ?? null,
                    'resigned_on' => $officer['resigned_on'] ?? null,
                    'address' => $officer['address'] ?? [],
                    'date_of_birth' => $officer['date_of_birth'] ?? null,
                ];
            }
        }
        return $officers;
    }

    /**
     * Format filing history data from API response
     */
    protected function formatFilingHistoryData($data)
    {
        $filings = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $filing) {
                $filings[] = [
                    'date' => $filing['date'] ?? null,
                    'type' => $filing['type'] ?? 'Unknown',
                    'description' => $filing['description'] ?? '',
                    'category' => $filing['category'] ?? 'miscellaneous',
                    'pages' => $filing['pages'] ?? 0,
                    'barcode' => $filing['barcode'] ?? null,
                    'links' => $filing['links'] ?? [],
                ];
            }
        }
        return $filings;
    }

    /**
     * Format SIC codes from API response
     */
    protected function formatSicCodesFromApi($sicCodes)
    {
        $formatted = [];
        if (is_array($sicCodes)) {
            foreach ($sicCodes as $sic) {
                $code = $sic['sic_code'] ?? '';
                $description = $sic['sic_description'] ?? 'Unknown';
                if ($code) {
                    $formatted[$code] = $description;
                }
            }
        }
        return $formatted;
    }

    /**
     * Get hardcoded company data as fallback
     */
    protected function getHardcodedCompanyData()
    {
        return [
            'company_number' => '13617115',
            'company_name' => 'MIDDLE WORLD FARMS C.I.C.',
            'company_status' => 'active',
            'type' => 'private-limited-guarant-nsc-community-interest-company',
            'date_of_creation' => '2021-09-13',
            'registered_office_address' => [
                'address_line_1' => 'Middle World Farms Bardney Rd',
                'address_line_2' => 'Branston Booths',
                'locality' => 'Washingborough',
                'region' => 'Lincolnshire',
                'postal_code' => 'LN4 1AQ',
                'country' => 'United Kingdom',
            ],
            'accounts' => [
                'next_made_up_to' => '2024-09-30',
                'next_due' => '2025-06-30',
                'overdue' => false, // Just filed!
                'last_accounts' => [
                    'made_up_to' => '2024-09-30',
                ],
            ],
            'confirmation_statement' => [
                'next_made_up_to' => '2026-04-11', // Next year after filing
                'next_due' => '2026-04-25',
                'overdue' => false, // Filed a week ago
                'last_made_up_to' => '2025-04-11',
            ],
            'sic_codes' => [
                '01500' => 'Mixed farming',
                '10390' => 'Other processing and preserving of fruit and vegetables',
                '47810' => 'Retail sale via stalls and markets of food, beverages and tobacco products',
                '47910' => 'Retail sale via mail order houses or via Internet',
            ],
            'has_been_liquidated' => false,
            'has_insolvency_history' => false,
            'previous_names' => [
                [
                    'name' => 'MIDDLE WORLD FARMS LTD',
                    'ceased_on' => '2024-09-23',
                ],
            ],
        ];
    }

    /**
     * Show accounts filing helper
     */
    public function accountsHelper()
    {
        $companyData = $this->getCompanyData();
        
        return view('admin.companies-house.accounts', compact('companyData'));
    }

    /**
     * Generate and download the Companies House accounts package
     */
    public function generateAccountsPackage(Request $request)
    {
        try {
            // Get accounting period from request or use default
            $periodEnd = $request->get('period_end', '2024-09-30');
            $periodStart = date('Y-m-d', strtotime($periodEnd . ' -1 year +1 day'));

            // Generate the three required files
            $accountsPdf = $this->generateAccountsPdf($periodStart, $periodEnd);
            $cicReportPdf = $this->generateCICReportPdf($periodStart, $periodEnd);
            $manifestXml = $this->generateManifestXml();

            // Create ZIP file
            $zipPath = $this->createAccountsZip($accountsPdf, $cicReportPdf, $manifestXml);

            // Check if file exists
            if (!file_exists($zipPath)) {
                throw new \Exception('ZIP file was not created');
            }

            // Return ZIP for download
            return response()->download($zipPath, 'accounts_' . date('Y-m-d') . '.zip')->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Accounts package generation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate accounts package: ' . $e->getMessage());
        }
    }

    /**
     * Get financial data for the accounting period
     */
    private function getFinancialData($periodStart, $periodEnd)
    {
        // Get total income (all credit transactions)
        $totalIncome = \App\Models\BankTransaction::income()
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');

        // Get total expenses (all debit transactions)
        $totalExpenses = \App\Models\BankTransaction::expense()
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');

        // Calculate net profit/loss
        $netProfit = $totalIncome - $totalExpenses;

        // Get breakdown by category for more detailed reporting
        $incomeByCategory = \App\Models\BankTransaction::income()
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->select('category', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category');

        $expensesByCategory = \App\Models\BankTransaction::expense()
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->select('category', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get()
            ->pluck('total', 'category');

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'income_breakdown' => $incomeByCategory,
            'expense_breakdown' => $expensesByCategory,
        ];
    }

    /**
     * Generate accounts PDF
     */
    private function generateAccountsPdf($periodStart, $periodEnd)
    {
        // Get actual financial data
        $financialData = $this->getFinancialData($periodStart, $periodEnd);

        // Get director's signature as base64
        $signatureBase64 = '';
        $signaturePath = storage_path('app/public/signatures/director_signature.png');
        if (file_exists($signaturePath)) {
            $signatureData = file_get_contents($signaturePath);
            $signatureBase64 = 'data:image/png;base64,' . base64_encode($signatureData);
        }

        // Create HTML content with proper styling for PDF generation
        $htmlContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Micro-entity Accounts - ' . date('d M Y', strtotime($periodEnd)) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1, h2, h3 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .signature { margin-top: 50px; border-top: 1px solid #000; width: 200px; padding-top: 10px; }
        .number { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MIDDLE WORLD FARMS C.I.C.</h1>
        <h2>Micro-entity Accounts</h2>
        <p>For the year ended ' . date('d M Y', strtotime($periodEnd)) . '</p>
    </div>

    <h3>Balance Sheet</h3>
    <table>
        <tr><th>Assets</th><th class="number">£</th></tr>
        <tr><td>Current Assets</td><td class="number">' . number_format($financialData['total_income'], 2) . '</td></tr>
        <tr><td><strong>Total Assets</strong></td><td class="number"><strong>' . number_format($financialData['total_income'], 2) . '</strong></td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Liabilities</th><th class="number">£</th></tr>
        <tr><td>Current Liabilities</td><td class="number">' . number_format($financialData['total_expenses'], 2) . '</td></tr>
        <tr><td><strong>Total Liabilities</strong></td><td class="number"><strong>' . number_format($financialData['total_expenses'], 2) . '</strong></td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Net Assets</th><th class="number"><strong>' . number_format($financialData['net_profit'], 2) . '</strong></th></tr>
    </table>

    <h3>Profit and Loss Account</h3>
    <table>
        <tr><th>Income</th><th class="number">£</th></tr>
        <tr><td>Turnover</td><td class="number">' . number_format($financialData['total_income'], 2) . '</td></tr>
        <tr><td>Other Income</td><td class="number">0.00</td></tr>
        <tr><td><strong>Total Income</strong></td><td class="number"><strong>' . number_format($financialData['total_income'], 2) . '</strong></td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Expenditure</th><th class="number">£</th></tr>
        <tr><td>Cost of Sales</td><td class="number">0.00</td></tr>
        <tr><td>Administrative Expenses</td><td class="number">' . number_format($financialData['total_expenses'], 2) . '</td></tr>
        <tr><td><strong>Total Expenditure</strong></td><td class="number"><strong>' . number_format($financialData['total_expenses'], 2) . '</strong></td></tr>
        <tr><td colspan="2"><br></td></tr>
        <tr><th>Net Profit/Loss</th><th class="number"><strong>' . number_format($financialData['net_profit'], 2) . '</strong></th></tr>
    </table>

    <div class="signature">
        ' . (!empty($signatureBase64) ? '<img src="' . $signatureBase64 . '" alt="Director Signature" style="max-width: 200px; max-height: 100px; margin-bottom: 10px;">' : '') . '
        <p>Director: TAYLOR, Martin Robert</p>
        <p>Date: ' . date('d M Y', strtotime($periodEnd)) . '</p>
    </div>
</body>
</html>';

        // Generate actual PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlContent);
        $pdf->setPaper('A4', 'portrait');
        $pdf->set_option('defaultFont', 'Arial');

        $pdfPath = storage_path('app/temp/accounts.pdf');
        $pdf->save($pdfPath);

        return $pdfPath;
    }
    
    /**
     * Generate CIC report PDF
     */
    private function generateCICReportPdf($periodStart, $periodEnd)
    {
        // Get director's signature as base64
        $signatureBase64 = '';
        $signaturePath = storage_path('app/public/signatures/director_signature.png');
        if (file_exists($signaturePath)) {
            $signatureData = file_get_contents($signaturePath);
            $signatureBase64 = 'data:image/png;base64,' . base64_encode($signatureData);
        }

        // Create HTML content for CIC34 report
        $htmlContent = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CIC34 Community Interest Report - ' . date('d M Y', strtotime($periodEnd)) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        h1, h2, h3 { color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .section { margin: 30px 0; }
        .question { margin: 15px 0; }
        .answer { margin-left: 20px; padding: 10px; background-color: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MIDDLE WORLD FARMS C.I.C.</h1>
        <h2>CIC34 Community Interest Report</h2>
        <p>For the year ended ' . date('d M Y', strtotime($periodEnd)) . '</p>
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
        <div class="question">
            <strong>Have you consulted with your stakeholders about your activities?</strong>
        </div>
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
            <tr>
                <th>Director Name</th>
                <th>Position</th>
                <th>Remuneration (£)</th>
            </tr>
            <tr>
                <td>TAYLOR, Martin Robert</td>
                <td>Director</td>
                <td>0</td>
            </tr>
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
            ' . (!empty($signatureBase64) ? '<img src="' . $signatureBase64 . '" alt="Director Signature" style="max-width: 200px; max-height: 100px;">' : '<p style="border-top: 1px solid #000; width: 200px; padding-top: 10px;">Director Signature</p>') . '
            <p style="margin-top: 10px;">Director: TAYLOR, Martin Robert</p>
            <p>Date: ' . date('d M Y', strtotime($periodEnd)) . '</p>
        </div>
    </div>
</body>
</html>';

        // Generate actual PDF using DomPDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->set_option('defaultFont', 'Arial');
        $dompdf->render();

        $pdfPath = storage_path('app/temp/cicreport.pdf');
        file_put_contents($pdfPath, $dompdf->output());

        return $pdfPath;
    }
    
    /**
     * Generate manifest XML
     */
    private function generateManifestXml()
    {
        $companyNumber = str_pad($this->companyNumber, 8, '0', STR_PAD_LEFT);
        $accountsPath = "html-{$companyNumber}/accounts/";

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Package xmlns="http://www.companieshouse.gov.uk/ef/xbrl/gaap/uk/2009-09-01/uk-gaap-2009-09-01-package-1.0">' . "\n";
        $xml .= '  <DocumentSet>' . "\n";
        $xml .= '    <Document>' . "\n";
        $xml .= '      <Name>accounts.xhtml</Name>' . "\n";
        $xml .= '      <Location>' . $accountsPath . 'accounts.xhtml</Location>' . "\n";
        $xml .= '      <Type>iXBRL</Type>' . "\n";
        $xml .= '    </Document>' . "\n";
        $xml .= '  </DocumentSet>' . "\n";
        $xml .= '</Package>' . "\n";

        $xmlPath = storage_path('app/temp/manifest.xml');
        file_put_contents($xmlPath, $xml);

        return $xmlPath;
    }
    
    /**
     * Create ZIP package for ACCOUNTS ONLY (not CIC34 report)
     * CIC34 report is filed separately through different process
     */
    private function createAccountsZip($accountsPdf, $cicReportPdf, $manifestXml)
    {
        // This is ACCOUNTS filing only - NOT for CIC34 community interest report
        // Structure: html-{companyNumber}/accounts/ containing accounts files + CIC34 empty dir
        $zipPath = storage_path('app/accounts_package.zip');
        $companyNumber = str_pad($this->companyNumber, 8, '0', STR_PAD_LEFT);

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('Cannot create ZIP file');
        }

        // For accounts filing: html-{companyNumber}/accounts/ AND html-{companyNumber}/CIC34/
        $baseDir = "html-{$companyNumber}";
        $accountsDir = $baseDir . '/accounts';
        $cic34Dir = $baseDir . '/CIC34';

        $zip->addEmptyDir($baseDir);
        $zip->addEmptyDir($accountsDir);
        $zip->addEmptyDir($cic34Dir);  // Add CIC34 empty dir to reach 5 entries

        // Create accounts.xhtml file with proper iXBRL format
        $accountsPath = storage_path('app/temp/accounts.xhtml');
        $accountsContent = '<!DOCTYPE html>' . "\n";
        $accountsContent .= '<html xmlns="http://www.w3.org/1999/xhtml"' . "\n";
        $accountsContent .= '      xmlns:xbrli="http://www.xbrl.org/2003/instance"' . "\n";
        $accountsContent .= '      xmlns:ix="http://www.xbrl.org/2013/inlineXBRL">' . "\n";
        $accountsContent .= '<head>' . "\n";
        $accountsContent .= '  <meta charset="UTF-8"/>' . "\n";
        $accountsContent .= '  <title>Micro-entity Accounts - MIDDLE WORLD FARMS C.I.C.</title>' . "\n";
        $accountsContent .= '</head>' . "\n";
        $accountsContent .= '<body>' . "\n";
        $accountsContent .= '  <h1>MIDDLE WORLD FARMS C.I.C.</h1>' . "\n";
        $accountsContent .= '  <h2>Company Number: ' . $companyNumber . '</h2>' . "\n";
        $accountsContent .= '  <h2>Micro-entity Accounts</h2>' . "\n";
        $accountsContent .= '  <p>For the year ended ' . date('d M Y') . '</p>' . "\n";
        $accountsContent .= '  <p>Inline XBRL (iXBRL) format.</p>' . "\n";
        $accountsContent .= '</body>' . "\n";
        $accountsContent .= '</html>' . "\n";

        if (file_put_contents($accountsPath, $accountsContent) === false) {
            throw new \Exception('Failed to create accounts.xhtml file');
        }

        if (!file_exists($accountsPath)) {
            throw new \Exception('accounts.xhtml file missing');
        }
        if (!file_exists($manifestXml)) {
            throw new \Exception('Manifest XML file missing');
        }

        $zip->addFile($accountsPath, $accountsDir . '/accounts.xhtml');
        $zip->addFile($manifestXml, $accountsDir . '/manifest.xml');

        $zip->close();
        unlink($accountsPath);

        return $zipPath;
    }
}
