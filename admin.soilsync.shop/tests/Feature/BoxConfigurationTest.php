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
        $plan = VegboxPlan::factory()->create();

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
            'token_value' => 1, // Should default to 1
        ]);
    }

    public function test_can_update_box_configuration_without_token_value()
    {
        $plan = VegboxPlan::factory()->create();
        $boxConfig = BoxConfiguration::factory()->create(['plan_id' => $plan->id]);

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
            'token_value' => 1, // Should default to 1
        ]);
    }
}