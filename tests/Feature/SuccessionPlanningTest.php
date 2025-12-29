<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SuccessionPlanningTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_root_redirects_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.login.form'));
    }
}
