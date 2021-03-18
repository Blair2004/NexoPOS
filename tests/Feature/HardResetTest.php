<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HardResetTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        // Artisan::call( 'ns:reset' );
        $exitCode   =   Artisan::call( 'ns:setup', [ 
            '--admin_username'  =>  'blair2004',
            '--admin_email'     =>  'contact@nexopos.com',
            '--password'        =>  '123456',
            '--store_name'      =>  'NexoPOS 4.x'
        ]);

        dump( $exitCode );
    }
}
