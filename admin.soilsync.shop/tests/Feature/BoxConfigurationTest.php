<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BoxConfiguration;
use App\Models\BoxConfigurationItem;
use App\Models\VegboxPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BoxConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_box_configuration_without_token_value()
    {
        // Create a plan directly without factory
        $plan = VegboxPlan::create([
            'name' => ['en' => 'Test Plan'],
            'slug' => 'test-plan',
            'price' => 25.00,
            'currency' => 'GBP',
            'invoice_period' => 1,
            'invoice_interval' => 'month',
            'box_size' => 'medium',
            'delivery_frequency' => 'weekly',
            'max_deliveries_per_month' => 4,
        ]);

        $data = [
            'week_starting' => '2024-01-01',
            'plan_id' => $plan->id,
            'items' => [
                [
                    'product_id' => 1,
                    'item_name' => 'Test Product',
                    'quantity' => 2,
                    'price' => 5.00,
                    'unit' => 'kg',
                    'is_featured' => false,
                ]
            ]
        ];

        $response = $this->post(route('admin.box-configurations.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('box_configurations', [
            'week_starting' => '2024-01-01',
            'plan_id' => $plan->id,
        ]);

        $boxConfig = BoxConfiguration::where('plan_id', $plan->id)->first();
        $this->assertDatabaseHas('box_configuration_items', [
            'box_configuration_id' => $boxConfig->id,
            'item_name' => 'Test Product',
            'price_at_time' => 5.00,
        ]);
    }

    public function test_can_update_box_configuration_without_token_value()
    {
        // Create a plan directly
        $plan = VegboxPlan::create([
            'name' => ['en' => 'Test Plan'],
            'slug' => 'test-plan-2',
            'price' => 25.00,
            'currency' => 'GBP',
            'invoice_period' => 1,
            'invoice_interval' => 'month',
            'box_size' => 'medium',
            'delivery_frequency' => 'weekly',
            'max_deliveries_per_month' => 4,
        ]);
        
        $boxConfig = BoxConfiguration::create([
            'week_starting' => '2024-01-01',
            'plan_id' => $plan->id,
            'is_active' => true,
        ]);

        $data = [
            'items' => [
                [
                    'product_id' => 1,
                    'item_name' => 'Updated Product',
                    'quantity' => 3,
                    'price' => 7.50,
                    'unit' => 'bunch',
                    'is_featured' => true,
                ]
            ]
        ];

        $response = $this->put(route('admin.box-configurations.update', $boxConfig), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('box_configuration_items', [
            'box_configuration_id' => $boxConfig->id,
            'item_name' => 'Updated Product',
            'price_at_time' => 7.50,
        ]);
    }
}