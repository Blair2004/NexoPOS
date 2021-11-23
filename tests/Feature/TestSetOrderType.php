<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestSetOrderType extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_set_settings()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        ns()->option->set( 'ns_pos_order_types', [ 'takeaway', 'delivery' ]);
    }
}
