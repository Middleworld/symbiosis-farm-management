<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UpdateCustomerFunds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:update-funds {email} {amount} {--action=add}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update customer funds and payment method';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $amount = (float) $this->argument('amount');
        $action = $this->option('action');

        $this->info("Updating funds for customer: {$email}");
        $this->info("Amount: £{$amount}");
        $this->info("Action: {$action}");

        try {
            // Try to update funds via API
            $apiKey = env('MWF_API_KEY', 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h');
            
            $response = Http::withHeaders([
                'X-WC-API-Key' => $apiKey,
                'Content-Type' => 'application/json'
            ])->post('https://middleworldfarms.org/wp-json/mwf/v1/funds', [
                'email' => $email,
                'amount' => $amount,
                'action' => $action
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['success']) && $data['success']) {
                    $this->info('✅ Funds updated successfully via API');
                    $this->info('New balance: £' . ($data['new_balance'] ?? 'Unknown'));
                    return;
                }
            }

            $this->warn('API update failed, trying direct database update...');

            // Fallback: Direct database update
            $prefix = ''; // Don't use prefix since tables already have it
            
            // Find user by email
            $user = DB::connection('wordpress')
                ->table('D6sPMX_users')
                ->join('D6sPMX_usermeta', 'D6sPMX_users.ID', '=', 'D6sPMX_usermeta.user_id')
                ->where('D6sPMX_users.user_email', $email)
                ->where('D6sPMX_usermeta.meta_key', 'account_funds')
                ->select('D6sPMX_users.ID', 'D6sPMX_usermeta.meta_value')
                ->first();

            if (!$user) {
                // User doesn't have account_funds meta, create it
                $userId = DB::connection('wordpress')
                    ->table('D6sPMX_users')
                    ->where('user_email', $email)
                    ->value('ID');

                if (!$userId) {
                    $this->error("❌ User not found: {$email}");
                    return;
                }

                DB::connection('wordpress')
                    ->table('D6sPMX_usermeta')
                    ->insert([
                        'user_id' => $userId,
                        'meta_key' => 'account_funds',
                        'meta_value' => $amount
                    ]);

                $this->info("✅ Created account funds record: £{$amount}");
            } else {
                $currentFunds = (float) $user->meta_value;
                $newFunds = $action === 'add' ? $currentFunds + $amount : $amount;

                DB::connection('wordpress')
                    ->table('D6sPMX_usermeta')
                    ->where('user_id', $user->ID)
                    ->where('meta_key', 'account_funds')
                    ->update(['meta_value' => $newFunds]);

                $this->info("✅ Updated account funds: £{$currentFunds} → £{$newFunds}");
            }

            // Update payment method preference
            $this->updatePaymentMethod($email, 'funds');

        } catch (\Exception $e) {
            $this->error('❌ Error updating funds: ' . $e->getMessage());
        }
    }

    /**
     * Update customer's payment method preference
     */
    private function updatePaymentMethod($email, $method)
    {
        try {
            $userId = DB::connection('wordpress')
                ->table('D6sPMX_users')
                ->where('user_email', $email)
                ->value('ID');

            if (!$userId) {
                $this->warn("Could not find user ID for payment method update");
                return;
            }

            // Update or create payment method preference
            DB::connection('wordpress')
                ->table('D6sPMX_usermeta')
                ->updateOrInsert(
                    ['user_id' => $userId, 'meta_key' => 'payment_method_preference'],
                    ['meta_value' => $method]
                );

            $this->info("✅ Updated payment method to: {$method}");

        } catch (\Exception $e) {
            $this->warn('Could not update payment method: ' . $e->getMessage());
        }
    }
}
