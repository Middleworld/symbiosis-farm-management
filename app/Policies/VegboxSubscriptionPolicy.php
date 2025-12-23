<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VegboxSubscription;
use Illuminate\Auth\Access\Response;

class VegboxSubscriptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admins can view all subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VegboxSubscription $vegboxSubscription): bool
    {
        // Admins can view any subscription
        if ($user->isAdmin()) {
            return true;
        }
        
        // Users can only view their own subscriptions
        return $vegboxSubscription->subscriber_id === $user->id 
            && $vegboxSubscription->subscriber_type === User::class;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admins can create subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VegboxSubscription $vegboxSubscription): bool
    {
        // Admins can update any subscription
        if ($user->isAdmin()) {
            return true;
        }
        
        // Users can update their own subscriptions (limited fields)
        return $vegboxSubscription->subscriber_id === $user->id 
            && $vegboxSubscription->subscriber_type === User::class;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VegboxSubscription $vegboxSubscription): bool
    {
        // Only admins can delete subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VegboxSubscription $vegboxSubscription): bool
    {
        // Only admins can restore subscriptions
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VegboxSubscription $vegboxSubscription): bool
    {
        // Only admins can permanently delete subscriptions
        return $user->isAdmin();
    }
    
    /**
     * Determine whether the user can renew the model.
     */
    public function renew(User $user, VegboxSubscription $vegboxSubscription): bool
    {
        // Admins can renew any subscription
        if ($user->isAdmin()) {
            return true;
        }
        
        // Users can renew their own subscriptions
        return $vegboxSubscription->subscriber_id === $user->id 
            && $vegboxSubscription->subscriber_type === User::class;
    }
    
    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, VegboxSubscription $vegboxSubscription): bool
    {
        // Admins can cancel any subscription
        if ($user->isAdmin()) {
            return true;
        }
        
        // Users can cancel their own subscriptions
        return $vegboxSubscription->subscriber_id === $user->id 
            && $vegboxSubscription->subscriber_type === User::class;
    }
}
