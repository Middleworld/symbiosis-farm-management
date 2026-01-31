<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SuccessionPlanningTest extends TestCase
{
    /**
     * Test that root redirects to admin area (which requires admin login)
     */
    public function test_root_redirects_to_admin_area(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/admin');
    }
}
