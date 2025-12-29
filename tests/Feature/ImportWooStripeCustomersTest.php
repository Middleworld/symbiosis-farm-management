<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportWooStripeCustomersTest extends TestCase
{
    use RefreshDatabase;
    protected string $wordpressDbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wordpressDbPath = storage_path('testing-wordpress.sqlite');
        if (file_exists($this->wordpressDbPath)) {
            unlink($this->wordpressDbPath);
        }

        config()->set('database.connections.wordpress', [
            'driver' => 'sqlite',
            'database' => $this->wordpressDbPath,
            'prefix' => '',
        ]);

        touch($this->wordpressDbPath);

        Schema::connection('wordpress')->create('usermeta', function (Blueprint $table) {
            $table->increments('umeta_id');
            $table->unsignedInteger('user_id');
            $table->string('meta_key')->nullable();
            $table->text('meta_value')->nullable();
        });

        Schema::connection('wordpress')->create('woocommerce_payment_tokens', function (Blueprint $table) {
            $table->increments('token_id');
            $table->unsignedInteger('user_id');
            $table->string('gateway_id');
            $table->string('token');
            $table->string('type')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::connection('wordpress')->create('woocommerce_payment_tokenmeta', function (Blueprint $table) {
            $table->increments('meta_id');
            $table->unsignedInteger('payment_token_id');
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('wordpress')->dropIfExists('woocommerce_payment_tokenmeta');
        Schema::connection('wordpress')->dropIfExists('woocommerce_payment_tokens');
        Schema::connection('wordpress')->dropIfExists('usermeta');

        if (file_exists($this->wordpressDbPath)) {
            unlink($this->wordpressDbPath);
        }

        parent::tearDown();
    }

    public function test_it_imports_stripe_customers_and_tokens(): void
    {
        $wpUserId = 5001;

        User::factory()->create([
            'id' => 42,
            'woo_customer_id' => $wpUserId,
            'email' => 'vegbox@example.com',
        ]);

        DB::connection('wordpress')->table('usermeta')->insert([
            'user_id' => $wpUserId,
            'meta_key' => 'D6sPMX__wcpay_customer_id_live',
            'meta_value' => 'cus_test123',
        ]);

        $tokenId = DB::connection('wordpress')->table('woocommerce_payment_tokens')->insertGetId([
            'user_id' => $wpUserId,
            'gateway_id' => 'woocommerce_payments',
            'token' => 'pm_card_visa',
            'type' => 'card',
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('wordpress')->table('woocommerce_payment_tokenmeta')->insert([
            [
                'payment_token_id' => $tokenId,
                'meta_key' => '_stripe_card_brand',
                'meta_value' => 'visa',
            ],
            [
                'payment_token_id' => $tokenId,
                'meta_key' => '_stripe_card_last4',
                'meta_value' => '4242',
            ],
            [
                'payment_token_id' => $tokenId,
                'meta_key' => '_stripe_card_exp_month',
                'meta_value' => '12',
            ],
            [
                'payment_token_id' => $tokenId,
                'meta_key' => '_stripe_card_exp_year',
                'meta_value' => '2030',
            ],
        ]);

        Artisan::call('vegbox:import-woo-stripe', ['--user' => 42]);

        $user = User::find(42);

        $this->assertSame('cus_test123', $user->stripe_customer_id);
        $this->assertSame('pm_card_visa', $user->stripe_default_payment_method_id);
        $this->assertEquals('woocommerce_payments', $user->stripe_metadata['woo_gateway']);

        $this->assertDatabaseHas('user_payment_methods', [
            'user_id' => $user->id,
            'provider_payment_method_id' => 'pm_card_visa',
            'brand' => 'visa',
            'last4' => '4242',
            'is_default' => true,
        ]);
    }
}
