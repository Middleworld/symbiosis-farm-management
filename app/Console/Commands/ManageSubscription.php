<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WpApiService;
use App\Models\WooCommerceOrder;
use Illuminate\Support\Facades\DB;

class ManageSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:manage {email : Customer email address} {--action=info : Action to perform (info|fix-duplicate|refund|cancel|billing-history|add-credit|generate-email)} {--amount= : Amount to credit (for add-credit action)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage customer subscriptions - analyze duplicates, calculate refunds, check billing history, and fix issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $action = $this->option('action');
        
        $this->info("Managing subscription for: {$email}");
        $this->info("Action: {$action}");
        $this->line('');
        
        try {
            $wpApi = app(WpApiService::class);
            
            // Find customer subscriptions
            $subscriptions = $this->findCustomerSubscriptions($email, $wpApi);
            
            if (empty($subscriptions)) {
                $this->error("No subscriptions found for email: {$email}");
                return 1;
            }
            
            switch ($action) {
                case 'info':
                    $this->showSubscriptionInfo($subscriptions);
                    break;
                case 'fix-duplicate':
                    $this->fixDuplicateItems($subscriptions);
                    break;
                case 'refund':
                    $this->calculateRefund($subscriptions);
                    break;
                case 'cancel':
                    $this->cancelSubscription($subscriptions);
                    break;
                case 'billing-history':
                    $this->showBillingHistory($subscriptions, $wpApi);
                    break;
                case 'add-credit':
                    $this->addAccountCredit($subscriptions, $wpApi);
                    break;
                default:
                    $this->error("Unknown action: {$action}");
                    return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function findCustomerSubscriptions($email, $wpApi)
    {
        $allSubscriptions = $wpApi->getDeliveryScheduleData(200);
        $customerSubscriptions = [];
        
        foreach ($allSubscriptions as $sub) {
            $subEmail = $sub['billing']['email'] ?? '';
            if (strtolower($subEmail) === strtolower($email)) {
                $customerSubscriptions[] = $sub;
            }
        }
        
        return $customerSubscriptions;
    }
    
    private function showSubscriptionInfo($subscriptions)
    {
        foreach ($subscriptions as $index => $sub) {
            $this->line("=== Subscription " . ($index + 1) . " ===");
            $this->line("ID: " . ($sub['id'] ?? 'N/A'));
            $this->line("Status: " . ($sub['status'] ?? 'N/A'));
            $this->line("Customer: " . trim(($sub['billing']['first_name'] ?? '') . ' ' . ($sub['billing']['last_name'] ?? '')));
            $this->line("Email: " . ($sub['billing']['email'] ?? 'N/A'));
            $this->line("Total: £" . ($sub['total'] ?? 'N/A'));
            $this->line("Shipping: £" . ($sub['shipping_total'] ?? 'N/A'));
            
            $this->line("\nLine Items:");
            foreach ($sub['line_items'] ?? [] as $item) {
                $this->line("  - " . ($item['quantity'] ?? 1) . "x " . ($item['name'] ?? 'N/A') . " @ £" . ($item['price'] ?? 'N/A'));
            }
            
            $this->line("\nAddress:");
            $address = $sub['billing'] ?? [];
            $this->line("  " . ($address['address_1'] ?? 'N/A'));
            if (!empty($address['address_2'])) {
                $this->line("  " . $address['address_2']);
            }
            $this->line("  " . ($address['city'] ?? 'N/A') . ", " . ($address['postcode'] ?? 'N/A'));
            
            $this->line("\nFrequency Analysis:");
            $frequency = $this->getSubscriptionFrequency($sub);
            $this->line("  Frequency: " . $frequency);
            
            // Check for duplicates
            $duplicates = $this->findDuplicateItems($sub);
            if (!empty($duplicates)) {
                $this->warn("\n⚠️  DUPLICATE ITEMS DETECTED:");
                foreach ($duplicates as $duplicate) {
                    $this->line("  - " . $duplicate['name'] . " appears " . $duplicate['count'] . " times");
                }
            }
            
            $this->line("\n" . str_repeat('-', 50) . "\n");
        }
    }
    
    private function getSubscriptionFrequency($subscription)
    {
        // Check various places for frequency information
        foreach ($subscription['line_items'] ?? [] as $item) {
            foreach ($item['meta_data'] ?? [] as $meta) {
                if (isset($meta['key']) && strtolower($meta['key']) === 'frequency') {
                    return $meta['value'] ?? 'Unknown';
                }
            }
        }
        
        return 'Weekly'; // Default
    }
    
    private function findDuplicateItems($subscription)
    {
        $itemCounts = [];
        $duplicates = [];
        
        foreach ($subscription['line_items'] ?? [] as $item) {
            $itemName = $item['name'] ?? 'Unknown';
            $itemCounts[$itemName] = ($itemCounts[$itemName] ?? 0) + ($item['quantity'] ?? 1);
        }
        
        foreach ($itemCounts as $name => $count) {
            if ($count > 1) {
                $duplicates[] = [
                    'name' => $name,
                    'count' => $count
                ];
            }
        }
        
        return $duplicates;
    }
    
    private function fixDuplicateItems($subscriptions)
    {
        $this->info("Analyzing duplicate items for potential fixes...");
        
        foreach ($subscriptions as $index => $sub) {
            $duplicates = $this->findDuplicateItems($sub);
            
            if (empty($duplicates)) {
                $this->line("Subscription " . ($index + 1) . ": No duplicates found");
                continue;
            }
            
            $this->warn("Subscription " . ($index + 1) . " has duplicates:");
            foreach ($duplicates as $duplicate) {
                $this->line("  - " . $duplicate['name'] . " (quantity: " . $duplicate['count'] . ")");
            }
            
            if ($this->confirm("Would you like to fix these duplicates?")) {
                $this->line("To fix duplicates, you would need to:");
                $this->line("1. Log into WooCommerce admin");
                $this->line("2. Go to WooCommerce > Subscriptions");
                $this->line("3. Find subscription ID: " . ($sub['id'] ?? 'N/A'));
                $this->line("4. Edit the subscription and remove duplicate line items");
                $this->line("5. Save the subscription");
                
                $this->warn("Note: This command cannot directly modify WooCommerce data for security reasons.");
                $this->warn("Manual intervention through WooCommerce admin is required.");
            }
        }
    }
    
    private function calculateRefund($subscriptions)
    {
        $this->info("Calculating potential refunds...");
        
        foreach ($subscriptions as $index => $sub) {
            $this->line("=== Subscription " . ($index + 1) . " Refund Analysis ===");
            
            $duplicates = $this->findDuplicateItems($sub);
            $totalRefund = 0;
            
            if (empty($duplicates)) {
                $this->line("No duplicates found - no refund needed");
                continue;
            }
            
            foreach ($sub['line_items'] ?? [] as $item) {
                $itemName = $item['name'] ?? 'Unknown';
                $itemPrice = (float) ($item['price'] ?? 0);
                $itemQuantity = (int) ($item['quantity'] ?? 1);
                
                // Check if this item is duplicated
                foreach ($duplicates as $duplicate) {
                    if ($duplicate['name'] === $itemName && $duplicate['count'] > 1) {
                        // Calculate the correct price per item based on total
                        $totalItemPrice = $itemPrice * $itemQuantity;
                        $pricePerSingleItem = $totalItemPrice / $duplicate['count'];
                        
                        $excessQuantity = $duplicate['count'] - 1; // Excess beyond what should be 1
                        $refundAmount = $pricePerSingleItem * $excessQuantity;
                        $totalRefund += $refundAmount;
                        
                        $this->line("Item: " . $itemName);
                        $this->line("  Current quantity: " . $duplicate['count']);
                        $this->line("  Should be: 1");
                        $this->line("  Excess quantity: " . $excessQuantity);
                        $this->line("  Price per single item: £" . number_format($pricePerSingleItem, 2));
                        $this->line("  Refund per delivery: £" . number_format($refundAmount, 2));
                        $this->line("");
                    }
                }
            }
            
            if ($totalRefund > 0) {
                $this->info("Refund per delivery: £" . number_format($totalRefund, 2));
                
                // Calculate how many deliveries this affects
                $frequency = $this->getSubscriptionFrequency($sub);
                $this->line("Frequency: " . $frequency);
                
                if (strtolower($frequency) === 'fortnightly') {
                    $this->line("Since this is fortnightly, customer is overcharged £" . number_format($totalRefund, 2) . " every 2 weeks");
                    $monthlyOvercharge = $totalRefund * 2; // 2 fortnightly payments per month
                    $this->line("Monthly overcharge: £" . number_format($monthlyOvercharge, 2));
                } else {
                    $this->line("Weekly overcharge: £" . number_format($totalRefund, 2));
                    $monthlyOvercharge = $totalRefund * 4; // 4 weekly payments per month
                    $this->line("Monthly overcharge: £" . number_format($monthlyOvercharge, 2));
                }
                
                // Ask how many deliveries to refund
                $this->line("");
                $this->warn("To calculate total refund, specify how many deliveries have been affected:");
                $this->line("Example: If this has been wrong for 3 deliveries, total refund = £" . number_format($totalRefund, 2) . " × 3 = £" . number_format($totalRefund * 3, 2));
                
            } else {
                $this->line("No refund needed");
            }
            
            $this->line("");
        }
    }
    
    private function calculateWeeksAffected($subscription, $frequency)
    {
        // This is a simplified calculation - in practice, you'd need to check delivery history
        $subscriptionDate = $subscription['date_created'] ?? null;
        if (!$subscriptionDate) {
            return 0;
        }
        
        $startDate = new \DateTime($subscriptionDate);
        $now = new \DateTime();
        $interval = $now->diff($startDate);
        $weeksTotal = floor($interval->days / 7);
        
        if (strtolower($frequency) === 'fortnightly') {
            return floor($weeksTotal / 2);
        }
        
        return $weeksTotal;
    }
    
    private function cancelSubscription($subscriptions)
    {
        $this->warn("Subscription cancellation is not implemented in this command.");
        $this->line("To cancel a subscription:");
        $this->line("1. Log into WooCommerce admin");
        $this->line("2. Go to WooCommerce > Subscriptions");
        $this->line("3. Find the subscription and change status to 'Cancelled'");
        $this->line("4. Process any necessary refunds through WooCommerce > Orders");
    }
    
    private function showBillingHistory($subscriptions, $wpApi)
    {
        foreach ($subscriptions as $index => $sub) {
            $this->line("=== Subscription " . ($index + 1) . " Billing History ===");
            
            // For each subscription, get the associated orders
            $orders = $this->getSubscriptionOrders($sub['id'] ?? 0, $wpApi);
            
            if (empty($orders)) {
                $this->line("No billing history found for this subscription.");
                continue;
            }
            
            foreach ($orders as $order) {
                $this->line("Order ID: " . ($order['id'] ?? 'N/A'));
                $this->line("Date: " . ($order['date_created'] ?? 'N/A'));
                $this->line("Status: " . ($order['status'] ?? 'N/A'));
                $this->line("Total: £" . ($order['total'] ?? 'N/A'));
                
                $this->line("\nLine Items:");
                foreach ($order['line_items'] ?? [] as $item) {
                    $this->line("  - " . ($item['quantity'] ?? 1) . "x " . ($item['name'] ?? 'N/A') . " @ £" . ($item['price'] ?? 'N/A'));
                }
                
                $this->line("\n" . str_repeat('-', 50) . "\n");
            }
        }
    }
    
    private function getSubscriptionOrders($subscriptionId, $wpApi)
    {
        // This function retrieves orders associated with a given subscription ID
        $allOrders = $wpApi->getAllOrders(); // Assuming this method exists to get all orders
        $subscriptionOrders = [];
        
        foreach ($allOrders as $order) {
            if (($order['subscription_id'] ?? 0) == $subscriptionId) {
                $subscriptionOrders[] = $order;
            }
        }
        
        return $subscriptionOrders;
    }
    
    private function addAccountCredit($subscriptions, $wpApi)
    {
        $amount = $this->option('amount');
        
        if (!$amount) {
            $amount = $this->ask('Enter the credit amount (e.g., 40.00)');
        }
        
        if (!is_numeric($amount) || $amount <= 0) {
            $this->error("Invalid amount. Please enter a positive number.");
            return;
        }
        
        $amount = (float) $amount;
        
        foreach ($subscriptions as $index => $sub) {
            $customerEmail = $sub['billing']['email'] ?? '';
            $customerName = trim(($sub['billing']['first_name'] ?? '') . ' ' . ($sub['billing']['last_name'] ?? ''));
            $customerId = $sub['customer_id'] ?? null;
            
            if (!$customerId) {
                $this->error("Customer ID not found for subscription " . ($index + 1));
                continue;
            }
            
            $this->line("=== Adding Credit to Customer Account ===");
            $this->line("Customer: " . $customerName);
            $this->line("Email: " . $customerEmail);
            $this->line("Customer ID: " . $customerId);
            $this->line("Credit Amount: £" . number_format($amount, 2));
            $this->line("");
            
            if ($this->confirm("Are you sure you want to add £" . number_format($amount, 2) . " credit to this customer's account?")) {
                
                try {
                    // Method 1: Try to add credit via direct database update
                    $this->addCreditToDatabase($customerId, $amount, $customerEmail);
                    
                    $this->info("✅ Successfully added £" . number_format($amount, 2) . " credit to " . $customerName . "'s account");
                    
                    // Log the credit addition
                    $this->line("Credit added on: " . date('Y-m-d H:i:s'));
                    $this->line("Reason: Refund for duplicate subscription items");
                    
                } catch (\Exception $e) {
                    $this->error("Failed to add credit: " . $e->getMessage());
                    
                    // Fallback instructions
                    $this->warn("Manual credit addition required:");
                    $this->line("1. Log into WooCommerce admin");
                    $this->line("2. Go to WooCommerce > Customers");
                    $this->line("3. Find customer ID: " . $customerId);
                    $this->line("4. Add £" . number_format($amount, 2) . " to their account credit/balance");
                    $this->line("5. Add note: 'Refund for duplicate subscription items'");
                }
            } else {
                $this->line("Credit addition cancelled.");
            }
            
            $this->line("");
        }
    }
    
    private function addCreditToDatabase($customerId, $amount, $customerEmail)
    {
        // Add credit to the customer's account in the WordPress database
        // This uses the WordPress user meta table to store account credit
        
        $currentCredit = DB::connection('wordpress')
            ->table('usermeta')
            ->where('user_id', $customerId)
            ->where('meta_key', 'account_credit')
            ->value('meta_value');
            
        $currentCredit = (float) ($currentCredit ?? 0);
        $newCredit = $currentCredit + $amount;
        
        // Update or insert the credit amount
        DB::connection('wordpress')
            ->table('usermeta')
            ->updateOrInsert(
                ['user_id' => $customerId, 'meta_key' => 'account_credit'],
                ['meta_value' => $newCredit]
            );
            
        // Also log this transaction in a credit history table if it exists
        try {
            DB::connection('wordpress')
                ->table('usermeta')
                ->insert([
                    'user_id' => $customerId,
                    'meta_key' => 'credit_history_' . time(),
                    'meta_value' => json_encode([
                        'amount' => $amount,
                        'type' => 'refund',
                        'reason' => 'Duplicate subscription items refund',
                        'date' => date('Y-m-d H:i:s'),
                        'previous_balance' => $currentCredit,
                        'new_balance' => $newCredit
                    ])
                ]);
        } catch (\Exception $e) {
            // Credit history logging failed, but main credit addition succeeded
            $this->warn("Credit added but history logging failed: " . $e->getMessage());
        }
        
        $this->line("Previous credit balance: £" . number_format($currentCredit, 2));
        $this->line("New credit balance: £" . number_format($newCredit, 2));
    }
}
