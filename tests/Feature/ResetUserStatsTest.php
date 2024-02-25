<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ResetUserStatsTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testResetUserStats()
    {
        User::get()->each( function ( $user ) {
            $user->total_sales_count = 0;
            $user->total_sales = 0;
            $user->save();
        } );

        $this->assertTrue( true );
    }
}
