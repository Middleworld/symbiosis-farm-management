<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WordPressUser;
use App\Models\WooCommerceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmController extends Controller
{
    /**
     * Display contact information when phone rings (3CX integration)
     */
    public function contact(Request $request)
    {
        $phone = $request->input('phone', '');
        $name = $request->input('name', '');
        $email = $request->input('email', '');
        $customerId = $request->input('customer_id', '');
        
        // Clean phone number (remove spaces, dashes, country code variations)
        $cleanPhone = $this->cleanPhoneNumber($phone);
        
        // Search for customer by phone number, email, or ID
        $customer = null;
        $orders = collect();
        $notes = collect();
        
        if ($cleanPhone) {
            // Search WordPress/WooCommerce users by billing phone
            $customer = $this->findCustomerByPhone($cleanPhone);
        } elseif ($email) {
            // Search by email
            $customer = WordPressUser::where('user_email', $email)->first();
        } elseif ($customerId) {
            // Search by customer ID
            $customer = WordPressUser::find($customerId);
        }
        
        if ($customer) {
            // Get customer's recent orders
            $orders = $this->getCustomerOrders($customer->ID);
            
            // Get customer notes from admin system
            $notes = DB::connection('mysql')
                ->table('customer_notes')
                ->where('customer_email', $customer->user_email)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }
        
        return view('admin.crm.contact', [
            'phone' => $phone,
            'name' => $name,
            'email' => $email,
            'customer' => $customer,
            'orders' => $orders,
            'notes' => $notes,
            'cleanPhone' => $cleanPhone,
        ]);
    }
    
    /**
     * Find customer by phone number
     */
    private function findCustomerByPhone($phone)
    {
        // Try exact match first
        $customer = WordPressUser::whereHas('meta', function($query) use ($phone) {
            $query->where('meta_key', 'billing_phone')
                  ->where('meta_value', $phone);
        })->first();
        
        if ($customer) {
            return $customer;
        }
        
        // Try variations (UK phone numbers can be formatted differently)
        $variations = $this->getPhoneVariations($phone);
        
        foreach ($variations as $variation) {
            $customer = WordPressUser::whereHas('meta', function($query) use ($variation) {
                $query->where('meta_key', 'billing_phone')
                      ->where('meta_value', 'LIKE', '%' . $variation . '%');
            })->first();
            
            if ($customer) {
                return $customer;
            }
        }
        
        return null;
    }
    
    /**
     * Get customer's WooCommerce orders
     */
    private function getCustomerOrders($customerId, $limit = 10)
    {
        return WooCommerceOrder::where('post_type', 'shop_order')
            ->whereHas('meta', function($query) use ($customerId) {
                $query->where('meta_key', '_customer_user')
                      ->where('meta_value', $customerId);
            })
            ->with(['meta', 'items.meta'])
            ->orderBy('post_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->ID,
                    'order_number' => $order->getMeta('_order_number') ?: $order->ID,
                    'status' => str_replace('wc-', '', $order->post_status),
                    'date' => $order->post_date,
                    'total' => $order->getMeta('_order_total'),
                    'currency' => $order->getMeta('_order_currency') ?: 'GBP',
                    'payment_method' => $order->getMeta('_payment_method_title'),
                    'items_count' => $order->items->count(),
                    'items' => $order->items->map(function($item) {
                        return [
                            'name' => $item->order_item_name,
                            'quantity' => $item->getMeta('_qty'),
                            'total' => $item->getMeta('_line_total'),
                        ];
                    }),
                ];
            });
    }
    
    /**
     * Clean phone number for searching
     */
    private function cleanPhoneNumber($phone)
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);
        
        // Remove leading zeros and country codes
        $cleaned = preg_replace('/^(\+44|0044|44)/', '0', $cleaned);
        
        return $cleaned;
    }
    
    /**
     * Generate phone number variations for matching
     */
    private function getPhoneVariations($phone)
    {
        $variations = [];
        
        // Remove all formatting
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($digitsOnly) >= 10) {
            // UK variations
            $variations[] = '0' . substr($digitsOnly, -10); // 01234567890
            $variations[] = '+44' . substr($digitsOnly, -10, 10); // +441234567890
            $variations[] = '44' . substr($digitsOnly, -10, 10); // 441234567890
            
            // Mobile variations (07...)
            if (substr($digitsOnly, 0, 2) === '07' || substr($digitsOnly, -10, 2) === '07') {
                $variations[] = '07' . substr($digitsOnly, -9); // 07912345678
                $variations[] = '+447' . substr($digitsOnly, -9); // +447912345678
            }
        }
        
        return array_unique($variations);
    }
    
    /**
     * Add a note to customer record
     */
    public function addNote(Request $request)
    {
        $request->validate([
            'customer_email' => 'required|email',
            'note' => 'required|string',
            'note_type' => 'nullable|string',
        ]);
        
        DB::connection('mysql')->table('customer_notes')->insert([
            'customer_id' => $request->customer_id ?? 0,
            'customer_email' => $request->customer_email,
            'admin_id' => auth()->id() ?? 1,
            'note' => $request->note,
            'note_type' => $request->note_type ?? 'phone_call',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return back()->with('success', 'Note added successfully');
    }
}
