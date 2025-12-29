<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DriverNotificationService
{
    /**
     * Send optimized route to driver via email
     */
    public function sendRouteByEmail($driverEmail, $deliveries, $routeDetails, $deliveryDate = null)
    {
        try {
            $deliveryDate = $deliveryDate ?? date('Y-m-d');
            
            // Prepare email data
            $emailData = [
                'driver_email' => $driverEmail,
                'delivery_date' => $deliveryDate,
                'deliveries' => $deliveries,
                'route_details' => $routeDetails,
                'total_deliveries' => count($deliveries),
                'total_distance' => $routeDetails['total_distance'] ?? 'Unknown',
                'estimated_time' => $routeDetails['total_duration'] ?? 'Unknown'
            ];

            // Send email using Laravel's Mail facade
            Mail::send('emails.driver-route', $emailData, function ($message) use ($driverEmail, $deliveryDate) {
                $message->to($driverEmail)
                        ->subject('Delivery Route for ' . date('l, F j, Y', strtotime($deliveryDate)))
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('Route email sent successfully to: ' . $driverEmail);
            
            return [
                'success' => true,
                'message' => 'Route sent successfully via email'
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send route email: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send route summary to driver via SMS
     */
    public function sendRouteBySMS($driverPhone, $deliveries, $routeDetails, $deliveryDate = null)
    {
        try {
            $deliveryDate = $deliveryDate ?? date('Y-m-d');
            $totalDeliveries = count($deliveries);
            $totalDistance = $routeDetails['total_distance'] ?? 'Unknown';
            
            // Create SMS message
            $message = "Delivery Route for " . date('M j') . ":\n";
            $message .= "{$totalDeliveries} deliveries\n";
            $message .= "Total distance: {$totalDistance}\n";
            $message .= "Check email for full details.";
            
            // Send SMS (you'll need to configure your SMS provider)
            $result = $this->sendSMS($driverPhone, $message);
            
            if ($result['success']) {
                Log::info('Route SMS sent successfully to: ' . $driverPhone);
                return [
                    'success' => true,
                    'message' => 'Route sent successfully via SMS'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $result['error']
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to send route SMS: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to send SMS: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send SMS using configured provider (Twilio example)
     */
    private function sendSMS($phoneNumber, $message)
    {
        try {
            $twilioSid = config('services.twilio.sid');
            $twilioToken = config('services.twilio.token');
            $twilioFrom = config('services.twilio.from');
            
            if (!$twilioSid || !$twilioToken || !$twilioFrom) {
                return [
                    'success' => false,
                    'error' => 'SMS service not configured'
                ];
            }

            $response = Http::withBasicAuth($twilioSid, $twilioToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json", [
                    'From' => $twilioFrom,
                    'To' => $phoneNumber,
                    'Body' => $message
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_sid' => $response->json()['sid'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'SMS API error: ' . $response->body()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'SMS sending failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate Google Maps link for the route
     */
    public function generateMapsLink($deliveries, $startLocation = null)
    {
        $start = $startLocation ?? config('services.delivery.depot_address', 'Middleworld Farms, UK');
        $waypoints = [];
        
        foreach ($deliveries as $delivery) {
            $address = $this->formatAddress($delivery);
            if ($address) {
                $waypoints[] = urlencode($address);
            }
        }
        
        if (empty($waypoints)) {
            return null;
        }
        
        $waypointsStr = implode('/', $waypoints);
        return "https://www.google.com/maps/dir/" . urlencode($start) . "/" . $waypointsStr . "/" . urlencode($start);
    }

    /**
     * Format delivery address
     */
    private function formatAddress($delivery)
    {
        if (isset($delivery['address']) && is_array($delivery['address'])) {
            return implode(', ', array_filter($delivery['address']));
        } elseif (isset($delivery['address']) && is_string($delivery['address'])) {
            return $delivery['address'];
        }
        
        return null;
    }

    /**
     * Generate delivery manifest for driver
     */
    public function generateDeliveryManifest($deliveries, $routeDetails)
    {
        $manifest = [
            'summary' => [
                'total_deliveries' => count($deliveries),
                'total_distance' => $routeDetails['total_distance'] ?? 'Unknown',
                'estimated_time' => $routeDetails['total_duration'] ?? 'Unknown',
                'date' => date('Y-m-d')
            ],
            'deliveries' => []
        ];

        foreach ($deliveries as $index => $delivery) {
            $manifest['deliveries'][] = [
                'stop_number' => $index + 1,
                'customer_name' => $delivery['name'] ?? 'Unknown',
                'address' => $this->formatAddress($delivery),
                'phone' => $delivery['phone'] ?? '',
                'products' => $delivery['products'] ?? [],
                'special_instructions' => $delivery['notes'] ?? '',
                'estimated_delivery_time' => $this->calculateEstimatedTime($index, $routeDetails)
            ];
        }

        return $manifest;
    }

    /**
     * Calculate estimated delivery time for each stop
     */
    private function calculateEstimatedTime($stopIndex, $routeDetails)
    {
        // Simple estimation: assume 15 minutes per stop + travel time
        $baseTime = strtotime('08:00'); // Start at 8 AM
        $timePerStop = 15 * 60; // 15 minutes per stop
        $estimatedTime = $baseTime + ($stopIndex * $timePerStop);
        
        return date('H:i', $estimatedTime);
    }
}
