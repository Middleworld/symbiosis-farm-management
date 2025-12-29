<?php

declare(strict_types=1);

use App\Models\VegboxPlan;
use App\Models\VegboxPlanFeature;
use App\Models\VegboxSubscription;
use Laravelcm\Subscriptions\Models\SubscriptionUsage;

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Tables
    |--------------------------------------------------------------------------
    |
    |
    */

    'tables' => [
        'plans' => 'vegbox_plans',
        'features' => 'vegbox_plan_features',
        'subscriptions' => 'vegbox_subscriptions',
        'subscription_usage' => 'vegbox_subscription_usage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Models
    |--------------------------------------------------------------------------
    |
    | Models used to manage subscriptions. You can replace to use your own models,
    | but make sure that you have the same functionalities or that your models
    | extend from each model that you are going to replace.
    |
    */

    'models' => [
        'plan' => VegboxPlan::class,
        'feature' => VegboxPlanFeature::class,
        'subscription' => VegboxSubscription::class,
        'subscription_usage' => SubscriptionUsage::class,
    ],

];
