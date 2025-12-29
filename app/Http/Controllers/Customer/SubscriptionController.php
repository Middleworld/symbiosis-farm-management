<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\VegboxSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * Show customer's subscriptions
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('customer.login');
        }
        
        $subscriptions = VegboxSubscription::with(['plan'])
            ->where('subscriber_id', $user->id)
            ->where('subscriber_type', 'App\\Models\\User')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('customer.subscriptions.index', compact('subscriptions'));
    }
    
    /**
     * Show single subscription details
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('customer.login');
        }
        
        $subscription = VegboxSubscription::with(['plan'])
            ->where('id', $id)
            ->where('subscriber_id', $user->id)
            ->where('subscriber_type', 'App\\Models\\User')
            ->firstOrFail();
        
        // Get payment history from logs or separate table
        $payments = $this->getPaymentHistory($subscription);
        
        return view('customer.subscriptions.show', compact('subscription', 'payments'));
    }
    
    /**
     * Pause subscription
     */
    public function pause(Request $request, $id)
    {
        $user = Auth::user();
        
        $subscription = VegboxSubscription::where('id', $id)
            ->where('subscriber_id', $user->id)
            ->where('subscriber_type', 'App\\Models\\User')
            ->firstOrFail();
        
        $request->validate([
            'pause_until' => 'required|date|after:today'
        ]);
        
        $subscription->update([
            'pause_until' => $request->pause_until
        ]);
        
        Log::info('Customer paused subscription', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'pause_until' => $request->pause_until
        ]);
        
        return redirect()->route('customer.subscriptions.show', $subscription->id)
            ->with('success', 'Subscription paused until ' . date('F j, Y', strtotime($request->pause_until)));
    }
    
    /**
     * Resume subscription
     */
    public function resume(Request $request, $id)
    {
        $user = Auth::user();
        
        $subscription = VegboxSubscription::where('id', $id)
            ->where('subscriber_id', $user->id)
            ->where('subscriber_type', 'App\\Models\\User')
            ->firstOrFail();
        
        $subscription->update([
            'pause_until' => null
        ]);
        
        Log::info('Customer resumed subscription', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id
        ]);
        
        return redirect()->route('customer.subscriptions.show', $subscription->id)
            ->with('success', 'Subscription resumed successfully');
    }
    
    /**
     * Cancel subscription
     */
    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        
        $subscription = VegboxSubscription::where('id', $id)
            ->where('subscriber_id', $user->id)
            ->where('subscriber_type', 'App\\Models\\User')
            ->firstOrFail();
        
        $request->validate([
            'reason' => 'nullable|string|max:500'
        ]);
        
        $subscription->update([
            'canceled_at' => now(),
            'cancels_at' => now()->addDays(30) // Let them finish the billing period
        ]);
        
        Log::info('Customer cancelled subscription', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'reason' => $request->reason
        ]);
        
        // TODO: Send cancellation notification email
        
        return redirect()->route('customer.subscriptions.show', $subscription->id)
            ->with('success', 'Subscription cancelled. Your subscription will remain active until ' . $subscription->cancels_at->format('F j, Y'));
    }
    
    /**
     * Update delivery address
     */
    public function updateAddress(Request $request, $id)
    {
        $user = Auth::user();
        
        $subscription = VegboxSubscription::where('id', $id)
            ->where('subscriber_id', $user->id)
            ->where('subscriber_type', 'App\\Models\\User')
            ->firstOrFail();
        
        $request->validate([
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'postcode' => 'required|string|max:20',
            'phone' => 'nullable|string|max:20'
        ]);
        
        // TODO: Store in proper delivery_addresses table
        // For now, store in subscription meta or update user profile
        
        Log::info('Customer updated delivery address', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id
        ]);
        
        return redirect()->route('customer.subscriptions.show', $subscription->id)
            ->with('success', 'Delivery address updated successfully');
    }
    
    /**
     * Update delivery day
     */
    public function updateDeliveryDay(Request $request, $id)
    {
        $user = Auth::user();
        
        $subscription = VegboxSubscription::where('id', $id)
            ->where('subscriber_id', $user->id)
            ->where('subscriber_type', 'App\\Models\\User')
            ->firstOrFail();
        
        $request->validate([
            'delivery_day' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'
        ]);
        
        $subscription->update([
            'delivery_day' => $request->delivery_day
        ]);
        
        Log::info('Customer updated delivery day', [
            'subscription_id' => $subscription->id,
            'user_id' => $user->id,
            'new_delivery_day' => $request->delivery_day
        ]);
        
        return redirect()->route('customer.subscriptions.show', $subscription->id)
            ->with('success', 'Delivery day updated to ' . ucfirst($request->delivery_day));
    }
    
    /**
     * Get payment history for subscription
     */
    private function getPaymentHistory(VegboxSubscription $subscription)
    {
        // TODO: Query from subscription_orders table when it exists
        // For now, return empty or query from logs
        
        return [];
    }
}
