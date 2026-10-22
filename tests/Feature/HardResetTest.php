<?php

namespace Tests\Feature;

use App\Models\PaymentType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HardResetTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_hard_reset_system()
    {
        Schema::create( 'hard_reset_probe', function ( Blueprint $table ): void {
            $table->id();
        } );

        Artisan::call( 'ns:reset --mode=hard' );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/setup/configuration', [
                'ns_store_name' => env( 'NS_RESET_APPNAME', 'NexoPOS' ),
                'admin_email' => env( 'NS_RESET_MAIL', 'contact@nexopos.com' ),
                'admin_username' => env( 'NS_RESET_USERNAME', 'admin' ),
                'password' => env( 'NS_RESET_PASSWORD', 123456 ),
                'confirm_password' => env( 'NS_RESET_PASSWORD', 123456 ),
            ] );

        $response->assertStatus( 200 );
        $this->assertFalse( Schema::hasTable( 'hard_reset_probe' ) );

        // Check if 3 order payments have been created
        $paymentTypeCount = PaymentType::count();
        $this->assertEquals( 3, $paymentTypeCount );

        return $this->assertTrue( true );
    }
}
