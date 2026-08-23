<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TransactionActionRule;
use App\Models\TransactionActionRuleLine;
use App\Services\Helper;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResetTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_reset_does_not_stack_accounting_rule_lines(): void
    {
        if ( Helper::installed() ) {
            Sanctum::actingAs(
                Role::namespace( 'admin' )->users->first(),
                ['*']
            );

            for ( $attempt = 0; $attempt < 2; $attempt++ ) {
                $response = $this->withSession( $this->app[ 'session' ]->all() )
                    ->json( 'POST', 'api/reset', [
                        'mode' => 'wipe_plus_grocery',
                        'create_sales' => true,
                        'create_procurements' => true,
                    ] );

                $response->assertJson( [
                    'status' => 'success',
                ] );

                $response->assertStatus( 200 );
                $this->assertSame( 25, TransactionActionRule::query()->count() );
                $this->assertSame( 55, TransactionActionRuleLine::query()->count() );
            }
        } else {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/hard-reset', [
                    'authorization' => env( 'NS_AUTHORIZATION' ),
                ] );

            $response->assertJson( [
                'status' => 'success',
            ] );
        }
    }
}
