<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CustomerSMSService
{
    /**
     * Send SMS to customer for re-engagement
     */
    public function sendReengagementSMS($phoneNumber, $customerName, $messageType = 'welcome_back')
    {
        $messages = [
            'welcome_back' => "Hi {$customerName}! We've missed you at Middleworld Farms. Fresh veg boxes available - reply YES to restart deliveries!",
            'special_offer' => "Hi {$customerName}! Special offer: 20% off your next veg box. Fresh, local, organic. Reply INTERESTED to claim!",
            'seasonal' => "Hi {$customerName}! Our seasonal veg boxes are back. Reply START to begin weekly deliveries again!",
            'custom' => $customerName ? "Hi {$customerName}! " : ""
        ];

        $message = $messages[$messageType] ?? $messages['welcome_back'];

        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Send bulk SMS campaign to customer list
     */
    public function sendBulkCampaign($phoneNumbers, $message, $customerNames = [])
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($phoneNumbers as $index => $phone) {
            $customerName = $customerNames[$index] ?? null;

            // Personalize message if name available
            $personalizedMessage = $customerName
                ? str_replace('{name}', $customerName, $message)
                : $message;

            $result = $this->sendSMS($phone, $personalizedMessage);

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }

            $results[] = [
                'phone' => $phone,
                'success' => $result['success'],
                'error' => $result['error'] ?? null
            ];

            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 seconds
        }

        Log::info("Bulk SMS campaign completed: {$successCount} sent, {$failCount} failed");

        return [
            'success' => true,
            'total_sent' => $successCount,
            'total_failed' => $failCount,
            'results' => $results
        ];
    }

    /**
     * Send SMS using configured provider (Twilio)
     */
    private function sendSMS($phoneNumber, $message)
    {
        try {
            // Get Twilio credentials from settings
            $twilioSid = \App\Http\Controllers\Admin\SettingsController::getApiKey('twilio_sid');
            $twilioToken = \App\Http\Controllers\Admin\SettingsController::getApiKey('twilio_token');
            $twilioFrom = \App\Http\Controllers\Admin\SettingsController::getApiKey('twilio_from');

            if (!$twilioSid || !$twilioToken || !$twilioFrom) {
                return [
                    'success' => false,
                    'error' => 'SMS service not configured. Please set up Twilio credentials in settings.'
                ];
            }

            // Ensure phone number is in international format
            $phoneNumber = $this->formatPhoneNumber($phoneNumber);

            $response = Http::withBasicAuth($twilioSid, $twilioToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json", [
                    'From' => $twilioFrom,
                    'To' => $phoneNumber,
                    'Body' => $message
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Customer SMS sent successfully', [
                    'to' => $phoneNumber,
                    'sid' => $responseData['sid'] ?? null,
                    'status' => $responseData['status'] ?? null
                ]);

                return [
                    'success' => true,
                    'message_sid' => $responseData['sid'] ?? null,
                    'status' => $responseData['status'] ?? 'sent'
                ];
            } else {
                $error = $response->json();
                Log::error('Customer SMS failed', [
                    'to' => $phoneNumber,
                    'error_code' => $error['code'] ?? null,
                    'error_message' => $error['message'] ?? $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'SMS API error: ' . ($error['message'] ?? $response->body())
                ];
            }

        } catch (\Exception $e) {
            Log::error('Customer SMS sending failed: ' . $e->getMessage(), [
                'to' => $phoneNumber,
                'message_length' => strlen($message)
            ]);

            return [
                'success' => false,
                'error' => 'SMS sending failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // If it starts with 0, replace with +44 (UK)
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '+44' . substr($phoneNumber, 1);
        }

        // If it doesn't start with +, add +
        if (substr($phoneNumber, 0, 1) !== '+') {
            $phoneNumber = '+' . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Get SMS delivery statistics
     */
    public function getDeliveryStats()
    {
        // This would integrate with Twilio API to get delivery stats
        // For now, return placeholder
        return [
            'total_sent' => 0,
            'delivered' => 0,
            'failed' => 0,
            'pending' => 0
        ];
    }
}