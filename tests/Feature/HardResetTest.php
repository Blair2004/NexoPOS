<?php

namespace Tests\Feature;

use App\Models\PaymentType;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HardResetTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testHardResetSystem()
    {
        Artisan::call( 'ns:reset --mode=hard' );

        Artisan::call( 'ns:setup', [
            '--admin_username' => env( 'NS_RESET_USERNAME', 'admin' ),
            '--admin_email' => env( 'NS_RESET_MAIL', 'contact@nexopos.com' ),
            '--admin_password' => env( 'NS_RESET_PASSWORD', 123456 ),
            '--store_name' => env( 'NS_RESET_APPNAME', 'NexoPOS' ),
        ] );

        // Check if 3 order payments have been created
        $paymentTypeCount = PaymentType::count();
        $this->assertEquals( 3, $paymentTypeCount );

        ns()->option->setDefault();

        return $this->assertTrue( true );
    }
}
