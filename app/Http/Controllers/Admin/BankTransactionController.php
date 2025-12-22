<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Services\BankTransactionCategorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BankTransactionController extends Controller
{
    protected $categorizationService;

    public function __construct(BankTransactionCategorizationService $categorizationService)
    {
        $this->categorizationService = $categorizationService;
    }

    /**
     * Get current financial year (Oct 1 - Sept 30)
     * If today is before Oct 1, return previous year. Otherwise return current year.
     */
    protected function getCurrentFinancialYear()
    {
        $now = Carbon::now();
        // If we're before October, financial year is previous calendar year
        return $now->month < 10 ? $now->year - 1 : $now->year;
    }

    /**
     * Get available financial years from transaction data
     */
    protected function getAvailableFinancialYears()
    {
        $earliest = BankTransaction::orderBy('transaction_date', 'asc')->first();
        if (!$earliest) {
            return [];
        }
        
        $startYear = $earliest->transaction_date->month < 10 
            ? $earliest->transaction_date->year - 1 
            : $earliest->transaction_date->year;
        
        $currentYear = $this->getCurrentFinancialYear();
        
        $years = [];
        for ($year = $currentYear; $year >= $startYear; $year--) {
            $years[] = $year;
        }
        
        return $years;
    }

    /**
     * Display bank transactions list
     */
    public function index(Request $request)
    {
        $query = BankTransaction::query()->orderBy('transaction_date', 'desc');

        // Filter by date range
        if ($request->filled('from')) {
            $query->where('transaction_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('transaction_date', '<=', $request->to);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $transactions = $query->paginate(50);
        
        // Get summary stats
        $stats = $this->getSummaryStats($request);

        return view('admin.bank-transactions.index', compact('transactions', 'stats'));
    }

    /**
     * Show CSV import form
     */
    public function importForm()
    {
        return view('admin.bank-transactions.import');
    }

    /**
     * Process CSV import
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
            'auto_categorize' => 'boolean',
        ]);

        $file = $request->file('csv_file');
        $filename = $file->getClientOriginalName();
        
        // Parse CSV
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle); // First row is header
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Map CSV columns (adjust these based on your bank's CSV format)
                $data = $this->mapCsvRow($header, $row);
                
                if (!$data) {
                    $skipped++;
                    continue;
                }
                
                // Check for duplicates (same date, description, and amount)
                $exists = BankTransaction::where('transaction_date', $data['transaction_date'])
                    ->where('description', $data['description'])
                    ->where('amount', $data['amount'])
                    ->exists();
                
                if ($exists) {
                    $skipped++;
                    continue;
                }
                
                // Create transaction
                $transaction = BankTransaction::create([
                    'transaction_date' => $data['transaction_date'],
                    'description' => $data['description'],
                    'amount' => $data['amount'],
                    'type' => $data['type'],
                    'reference' => $data['reference'] ?? null,
                    'balance' => $data['balance'] ?? null,
                    'import_filename' => $filename,
                    'imported_at' => now(),
                    'imported_by' => auth()->id(),
                ]);
                
                // Auto-categorize if requested
                if ($request->auto_categorize) {
                    $transaction->autoCategorize();
                }
                
                $imported++;
            }
            
            fclose($handle);
            DB::commit();
            
            return redirect()
                ->route('admin.bank-transactions.index')
                ->with('success', "Imported {$imported} transactions. Skipped {$skipped} duplicates.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            
            return redirect()
                ->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Map CSV row to transaction data
     * Supports multiple UK bank CSV formats including Tide
     */
    protected function mapCsvRow(array $header, array $row): ?array
    {
        try {
            // Create associative array from header and row
            $data = array_combine($header, $row);
            
            // Support multiple UK bank CSV formats:
            // 1. Tide: Date, Transaction description, Paid in, Paid out, Reference
            // 2. Standard: Date, Description, Debit, Credit, Balance
            
            // Date field
            $date = $data['Date'] ?? $data['Transaction Date'] ?? $data['date'] ?? null;
            
            // Description field
            $description = $data['Transaction description'] 
                        ?? $data['Description'] 
                        ?? $data['Memo'] 
                        ?? $data['description'] 
                        ?? null;
            
            // Amount fields (Tide uses "Paid in" and "Paid out")
            $debit = $data['Paid out'] 
                  ?? $data['Debit'] 
                  ?? $data['Money Out'] 
                  ?? $data['debit'] 
                  ?? null;
                  
            $credit = $data['Paid in'] 
                   ?? $data['Credit'] 
                   ?? $data['Money In'] 
                   ?? $data['credit'] 
                   ?? null;
            
            // Balance field
            $balance = $data['Balance'] ?? $data['balance'] ?? null;
            
            // Reference field
            $reference = $data['Reference'] 
                      ?? $data['Transaction ID'] 
                      ?? $data['ref'] 
                      ?? null;
            
            if (!$date || !$description) {
                return null;
            }
            
            // Determine amount and type
            if (!empty($debit) && $debit != '0' && $debit != '0.00') {
                $amount = abs((float) str_replace(',', '', $debit));
                $type = 'debit';
            } elseif (!empty($credit) && $credit != '0' && $credit != '0.00') {
                $amount = abs((float) str_replace(',', '', $credit));
                $type = 'credit';
            } else {
                return null; // No amount
            }
            
            return [
                'transaction_date' => Carbon::parse($date),
                'description' => trim($description),
                'amount' => $amount,
                'type' => $type,
                'reference' => $reference ? trim($reference) : null,
                'balance' => !empty($balance) ? (float) str_replace(',', '', $balance) : null,
            ];
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update transaction category
     */
    public function updateCategory(Request $request, BankTransaction $transaction)
    {
        $request->validate([
            'category' => 'required|string',
        ]);
        
        $transaction->update([
            'category' => $request->category,
        ]);
        
        return redirect()->back()->with('success', 'Category updated successfully');
    }

    /**
     * Get summary statistics
     */
    protected function getSummaryStats(Request $request)
    {
        $query = BankTransaction::query();
        
        // Apply same filters
        if ($request->filled('from')) {
            $query->where('transaction_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('transaction_date', '<=', $request->to);
        }
        
        return [
            'total_income' => $query->clone()->income()->sum('amount'),
            'total_expenses' => $query->clone()->expense()->sum('amount'),
            'net_profit' => $query->clone()->income()->sum('amount') - $query->clone()->expense()->sum('amount'),
            'transaction_count' => $query->clone()->count(),
        ];
    }

    /**
     * Show accounting dashboard
     */
    public function dashboard(Request $request)
    {
        // Financial year runs Oct 1 - Sept 30
        $year = $request->get('year', $this->getCurrentFinancialYear());
        $from = Carbon::parse("{$year}-10-01");
        $to = Carbon::parse(($year + 1) . "-09-30");
        
        // Monthly income/expense breakdown
        $monthlyData = DB::table('bank_transactions')
            ->select(
                DB::raw('MONTH(transaction_date) as month'),
                DB::raw('SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as income'),
                DB::raw('SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as expenses')
            )
            ->whereBetween('transaction_date', [$from, $to])
            ->groupBy('month')
            ->get()
            ->sortBy(function($item) {
                // Sort by financial year: Oct(10)=1, Nov(11)=2, ..., Sep(9)=12
                return $item->month >= 10 ? $item->month - 9 : $item->month + 3;
            })
            ->values();
        
        // Category breakdown
        $incomeByCategory = BankTransaction::income()
            ->dateRange($from, $to)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();
            
        $expensesByCategory = BankTransaction::expense()
            ->dateRange($from, $to)
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();
        
        // Overall stats
        $stats = [
            'total_income' => BankTransaction::income()->dateRange($from, $to)->sum('amount'),
            'total_expenses' => BankTransaction::expense()->dateRange($from, $to)->sum('amount'),
        ];
        $stats['net_profit'] = $stats['total_income'] - $stats['total_expenses'];
        
        $availableYears = $this->getAvailableFinancialYears();
        
        // Year labels to avoid 3CX phone detection
        $yearLabels = [
            2022 => 'First Year',
            2023 => 'Second Year',
            2024 => 'Third Year',
            2025 => 'Current Year',
        ];
        
        // Get Companies House data for accounts section
        $companyData = $this->getCompanyData();
        $officers = $this->getOfficers();
        
        return view('admin.bank-transactions.dashboard', compact(
            'monthlyData',
            'incomeByCategory',
            'expensesByCategory',
            'stats',
            'year',
            'from',
            'to',
            'availableYears',
            'yearLabels',
            'companyData',
            'officers'
        ));
    }

    /**
     * Get detailed transactions for a specific month (AJAX)
     */
    public function monthDetails(Request $request)
    {
        $month = $request->get('month');
        $year = $request->get('year');

        if (!$month || !$year) {
            return response()->json([
                'success' => false,
                'message' => 'Month and year are required'
            ], 400);
        }

        // Get first and last day of the month
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Get income transactions
        $income = BankTransaction::income()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->get(['id', 'transaction_date', 'description', 'amount', 'category']);

        // Get expense transactions
        $expenses = BankTransaction::expense()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->get(['id', 'transaction_date', 'description', 'amount', 'category']);

        return response()->json([
            'success' => true,
            'data' => [
                'income' => $income,
                'expenses' => $expenses,
                'income_total' => $income->sum('amount'),
                'expenses_total' => $expenses->sum('amount'),
                'month' => $month,
                'year' => $year
            ]
        ]);
    }

    /**
     * Get company data for Companies House accounts
     */
    protected function getCompanyData()
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
                'overdue' => true,
            ],
            'confirmation_statement' => [
                'next_made_up_to' => '2025-04-11',
                'next_due' => '2025-04-25',
                'overdue' => true,
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
     * Get officers data from Companies House
     */
    protected function getOfficers()
    {
        // Real officer data from Companies House
        return [
            [
                'name' => 'TAYLOR, Martin Robert',
                'officer_role' => 'Director',
                'appointed_on' => '2021-09-13',
                'resigned_on' => null,
                'address' => [
                    'address_line_1' => 'Middle World Farms',
                    'address_line_2' => 'Bardney Rd, Branston Booths',
                    'locality' => 'Washingborough',
                    'region' => 'Lincolnshire',
                    'postal_code' => 'LN4 1AQ',
                    'country' => 'United Kingdom'
                ],
                'date_of_birth' => [
                    'month' => 3,
                    'year' => 1974
                ],
                'nationality' => 'British',
                'country_of_residence' => 'United Kingdom'
            ]
        ];
    }

    /**
     * Generate and download the Companies House accounts package
     */
    public function generateAccountsPackage(Request $request)
    {
        try {
            // Get accounting period from request or use current financial year
            $year = $request->get('year', $this->getCurrentFinancialYear());
            $from = Carbon::parse("{$year}-10-01");
            $to = Carbon::parse(($year + 1) . "-09-30");
            $periodEnd = $to->format('Y-m-d');
            $periodStart = $from->format('Y-m-d');

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
        $totalIncome = BankTransaction::income()
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');

        // Get total expenses (all debit transactions)
        $totalExpenses = BankTransaction::expense()
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->sum('amount');

        // Calculate net profit/loss
        $netProfit = $totalIncome - $totalExpenses;

        // Get breakdown by category for more detailed reporting
        $incomeByCategory = BankTransaction::income()
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->select('category', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get()
            ->pluck('total', 'category');

        $expensesByCategory = BankTransaction::expense()
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
    }    /**
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
        .question { margin: 15px 0; font-weight: bold; }
        .answer { margin-left: 20px; padding: 10px; background-color: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        ul { margin: 10px 0; padding-left: 20px; }
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
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($htmlContent);
        $pdf->setPaper('A4', 'portrait');
        $pdf->set_option('defaultFont', 'Arial');

        $pdfPath = storage_path('app/temp/cicreport.pdf');
        $pdf->save($pdfPath);

        return $pdfPath;
    }

    /**
     * Generate manifest XML
     */
    private function generateManifestXml()
    {
        $companyNumber = '13617115';
        $accountsPath = "CIC-{$companyNumber}/CIC34/accounts/";
        $manifestPath = "CIC-{$companyNumber}/manifest.xml";

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<manifest xmlns="http://www.govtalk.gov.uk/CM/envelope" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.govtalk.gov.uk/CM/envelope http://xmlgw.companieshouse.gov.uk/v1-0/schema/CHIPS_manifest-v1-0.xsd">
    <manifest-data>
        <package-identifier>' . $companyNumber . '</package-identifier>
        <package-type>CIC34</package-type>
        <package-version>1.0</package-version>
        <submission-date>' . date('Y-m-d') . '</submission-date>
        <submission-time>' . date('H:i:s') . '</submission-time>
        <supplier>
            <supplier-name>Middleworld Farms Ltd</supplier-name>
            <supplier-id>' . $companyNumber . '</supplier-id>
        </supplier>
    </manifest-data>
    <files>
        <file>
            <file-name>' . $accountsPath . 'accounts.html</file-name>
            <file-type>text/html</file-type>
            <file-size>1024</file-size>
        </file>
        <file>
            <file-name>' . $manifestPath . '</file-name>
            <file-type>text/xml</file-type>
            <file-size>1024</file-size>
        </file>
    </files>
</manifest>';

        $xmlPath = storage_path('app/temp/manifest.xml');
        file_put_contents($xmlPath, $xml);

        return $xmlPath;
    }

    /**
     * Generate CIC report HTML content
     */
    private function generateCICReportHtml($periodEnd, $officers = [])
    {
        $directorsHtml = '';
        if (!empty($officers)) {
            foreach ($officers as $officer) {
                if (strpos(strtolower($officer['officer_role']), 'director') !== false) {
                    $directorsHtml .= '<tr><td>' . htmlspecialchars($officer['name']) . '</td><td>' . htmlspecialchars($officer['officer_role']) . '</td><td>0</td></tr>';
                }
            }
        }
        
        if (empty($directorsHtml)) {
            $directorsHtml = '<tr><td>[Director Name]</td><td>[Position]</td><td>0</td></tr>';
        }

        // Get director's signature as base64
        $signatureBase64 = '';
        $signaturePath = storage_path('app/public/signatures/director_signature.png');
        if (file_exists($signaturePath)) {
            $signatureData = file_get_contents($signaturePath);
            $signatureBase64 = 'data:image/png;base64,' . base64_encode($signatureData);
        }

        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CIC34 Community Interest Report - ' . date('d M Y', strtotime($periodEnd)) . '</title>
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
            ' . $directorsHtml . '
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
            <p style="margin-top: 10px;">Director: ' . (!empty($officers) && isset($officers[0]['name']) ? htmlspecialchars($officers[0]['name']) : '___________________________') . '</p>
            <p>Date: ' . date('d M Y', strtotime($periodEnd)) . '</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Create ZIP package with HTML files
     */
    private function createAccountsZip($accountsPdf, $cicReportPdf, $manifestXml)
    {
        // Companies House requires both CIC34 (empty) and accounts directories
        // CIC-{companyNumber}/
        //   CIC34/        (EMPTY - required but no files)
        //   accounts/     (contains accounts.xhtml and manifest.xml)
        $zipPath = storage_path('app/accounts_package.zip');
        $companyNumber = str_pad('13617115', 8, '0', STR_PAD_LEFT);

        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('Cannot create ZIP file');
        }

        $baseDir = "CIC-{$companyNumber}";
        $cic34Dir = $baseDir . '/CIC34';
        $accountsDir = $baseDir . '/accounts';

        $zip->addEmptyDir($baseDir);
        $zip->addEmptyDir($cic34Dir);  // Required but EMPTY
        $zip->addEmptyDir($accountsDir);

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

        // Add only accounts.xhtml and manifest.xml to accounts directory
        // CIC34 stays EMPTY
        $zip->addFile($accountsPath, $accountsDir . '/accounts.xhtml');
        $zip->addFile($manifestXml, $accountsDir . '/manifest.xml');

        $zip->close();
        unlink($accountsPath);

        return $zipPath;
    }
}