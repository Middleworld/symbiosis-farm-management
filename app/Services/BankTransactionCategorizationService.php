<?php

namespace App\Services;

use App\Models\BankTransaction;
use Illuminate\Support\Str;

class BankTransactionCategorizationService
{
    /**
     * Auto-categorize a bank transaction based on description and amount
     */
    public function categorize(BankTransaction $transaction): string
    {
        $description = strtolower($transaction->description);
        $amount = abs($transaction->amount);
        
        // Income patterns (credits)
        if ($transaction->type === 'credit') {
            // Stripe bulk payments (payouts from online sales)
            if ($this->matches($description, ['stripe payments uk', 'stripe payout'])) {
                return 'online_sales'; // Stripe payouts from WooCommerce
            }
            
            // WooCommerce Payments (WooPay bulk transfers)
            if ($this->matches($description, ['woocommerce inc', 'woopayments'])) {
                return 'online_sales'; // WooPayments from WooCommerce
            }
            
            // Square payments (POS/online)
            if ($this->matches($description, ['square'])) {
                return 'pos_sales';
            }
            
            // Vegbox-specific income
            if ($this->matches($description, ['vegbox', 'veg box', 'subscription'])) {
                return 'vegbox_income';
            }
            
            // General online shop
            if ($this->matches($description, ['shop', 'online', 'order'])) {
                return 'online_sales';
            }
            
            // Grants and funding
            if ($this->matches($description, ['grant', 'funding', 'subsidy'])) {
                return 'grants_funding';
            }
            
            return 'other_income';
        }
        
        // Expense patterns (debits)
        if ($transaction->type === 'debit') {
            // Bank fees
            if ($this->matches($description, ['tide fee', 'bank fee', 'transaction fee'])) {
                return 'bank_fees';
            }
            
            // Seeds and supplies
            if ($this->matches($description, ['seed', 'compost', 'fertiliser', 'fertilizer', 'soil', 'nursery'])) {
                return 'seeds_supplies';
            }
            
            // Staff costs
            if ($this->matches($description, ['salary', 'wages', 'payroll', 'paye', 'ni ', 'pension'])) {
                return 'staff_costs';
            }
            
            // Utilities (expanded to include telecoms and software)
            if ($this->matches($description, [
                'electric', 'water', 'gas', 'fuel', 'diesel',
                'vodafone', 'ee ', 'o2', 'three', 'bt ', 'sky',
                'broadband', 'internet', 'phone', 'mobile'
            ])) {
                return 'utilities';
            }
            
            // Software and subscriptions
            if ($this->matches($description, [
                'subscription', 'software', 'saas', 'hosting',
                'ionos', 'godaddy', 'aws', 'google cloud', 'microsoft',
                'adobe', 'quickbooks', 'xero', 'sage',
                'amazon prime', 'netflix', 'spotify'
            ])) {
                return 'software_subscriptions';
            }
            
            // Equipment and machinery
            if ($this->matches($description, ['tractor', 'machinery', 'equipment', 'tools', 'repair'])) {
                return 'equipment';
            }
            
            // Vehicle expenses (separate from utilities)
            if ($this->matches($description, [
                'dvla', 'vehicle tax', 'mot', 'car insurance', 'van insurance',
                'petrol', 'diesel', 'fuel station', 'shell', 'bp ', 'tesco fuel',
                'parking', 'toll'
            ])) {
                return 'vehicle_expenses';
            }
            
            // Insurance (excluding vehicle insurance which is above)
            if ($this->matches($description, ['insurance', 'cover']) && 
                !$this->matches($description, ['car insurance', 'van insurance', 'vehicle insurance'])) {
                return 'insurance';
            }
            
            // Professional services
            if ($this->matches($description, [
                'accountant', 'solicitor', 'legal', 'consultant', 'fees',
                'accounting subscription'
            ])) {
                return 'professional_fees';
            }
            
            // Marketing
            if ($this->matches($description, ['advertising', 'marketing', 'website', 'facebook', 'instagram'])) {
                return 'marketing';
            }
            
            // Packaging
            if ($this->matches($description, ['packaging', 'box', 'bag', 'label'])) {
                return 'packaging';
            }
            
            // Food and supplies for business
            if ($this->matches($description, ['butcher', 'groceries', 'food supplier', 'catering'])) {
                return 'food_supplies';
            }
            
            // Tax
            if ($this->matches($description, ['hmrc', 'vat', 'tax', 'corporation tax'])) {
                return 'tax';
            }
            
            // Rent and rates
            if ($this->matches($description, ['rent', 'lease', 'rates', 'council tax'])) {
                return 'rent_rates';
            }
            
            return 'other_expense';
        }
        
        return 'uncategorized';
    }
    
    /**
     * Get category display name
     */
    public function getCategoryLabel(string $category): string
    {
        return match($category) {
            'vegbox_income' => 'Vegbox Subscriptions',
            'online_sales' => 'Online Shop Sales (Stripe/WooCommerce)',
            'pos_sales' => 'POS/Square Sales',
            'grants_funding' => 'Grants & Funding',
            'other_income' => 'Other Income',
            'seeds_supplies' => 'Seeds & Growing Supplies',
            'staff_costs' => 'Staff Wages & Costs',
            'utilities' => 'Utilities (Electric/Water/Telecoms)',
            'software_subscriptions' => 'Software & Subscriptions',
            'equipment' => 'Equipment & Machinery',
            'vehicle_expenses' => 'Vehicle Expenses (Tax/Fuel/Insurance)',
            'insurance' => 'Insurance',
            'professional_fees' => 'Professional Fees',
            'marketing' => 'Marketing & Advertising',
            'packaging' => 'Packaging Materials',
            'food_supplies' => 'Food & Supplies',
            'bank_fees' => 'Bank Fees',
            'tax' => 'Tax Payments',
            'rent_rates' => 'Rent & Rates',
            'other_expense' => 'Other Expenses',
            'uncategorized' => 'Uncategorized',
            default => ucwords(str_replace('_', ' ', $category)),
        };
    }
    
    /**
     * Get all available categories
     */
    public function getCategories(): array
    {
        return [
            'income' => [
                'vegbox_income' => 'Vegbox Subscriptions',
                'online_sales' => 'Online Shop Sales (Stripe/WooCommerce)',
                'pos_sales' => 'POS/Square Sales',
                'grants_funding' => 'Grants & Funding',
                'other_income' => 'Other Income',
            ],
            'expense' => [
                'seeds_supplies' => 'Seeds & Growing Supplies',
                'staff_costs' => 'Staff Wages & Costs',
                'utilities' => 'Utilities (Electric/Water/Telecoms)',
                'software_subscriptions' => 'Software & Subscriptions',
                'equipment' => 'Equipment & Machinery',
                'vehicle_expenses' => 'Vehicle Expenses (Tax/Fuel/Insurance)',
                'insurance' => 'Insurance',
                'professional_fees' => 'Professional Fees',
                'marketing' => 'Marketing & Advertising',
                'packaging' => 'Packaging Materials',
                'food_supplies' => 'Food & Supplies',
                'bank_fees' => 'Bank Fees',
                'tax' => 'Tax Payments',
                'rent_rates' => 'Rent & Rates',
                'other_expense' => 'Other Expenses',
            ],
        ];
    }
    
    /**
     * Check if description matches any of the keywords
     */
    private function matches(string $description, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (Str::contains($description, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
