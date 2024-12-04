<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaveSettingsTest extends TestCase
{
    use WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_save_settings()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        collect( Storage::disk( 'ns' )->files( 'app/Settings' ) )
            ->map( function ( $fileName ) {
                $fileName = collect( explode( '/', $fileName ) );
                $file = pathinfo( $fileName->last() );

                return 'App\\Settings\\' . $file[ 'filename' ];
            } )
            ->filter( function ( $class ) {
                $object = new $class;

                return array_key_exists( 'tabs', $object->getForm() );
            } )
            ->each( function ( $class ) {
                $object = new $class;
                $form = $object->getForm();

                $form = collect( $object->getForm()[ 'tabs' ] )->mapWithKeys( function ( $value, $key ) {
                    return [
                        $key => collect( $value[ 'fields' ] )
                            ->mapWithKeys( function ( $field ) {

                                if ( $field[ 'type' ] === 'select' && ! empty( $field[ 'options' ] ) ) {
                                    $rawField = collect( $field[ 'options' ] )->random();
                                }

                                return [
                                    $field[ 'name' ] => match ( $field[ 'name' ] ) {
                                        'ns_store_language' => 'en',
                                        'ns_currency_symbol' => '$',
                                        'ns_currency_iso' => 'USD',
                                        'ns_currency_thousand_separator' => '.',
                                        'ns_currency_decimal_separator' => ',',
                                        'ns_date_format' => 'Y-m-d',
                                        'ns_datetime_timezone' => 'Europe/London',
                                        'ns_datetime_format' => 'Y-m-d H:i',
                                        default => (
                                            match ( $field[ 'type' ] ) {
                                                'text', 'textarea' => strstr( $field[ 'name' ], 'email' ) ? $this->faker->email() : $this->faker->text( 20 ),
                                                'select' => ! empty( $field[ 'options' ] ) ? ( $rawField[ 'value' ] ?? '' ) : '',
                                                default => $field[ 'value' ]
                                            }
                                        )
                                    },
                                ];
                            } ),
                    ];
                } )->toArray();

                if ( ! empty( $object->getIdentifier() ) ) {
                    $response = $this
                        ->withSession( $this->app[ 'session' ]->all() )
                        ->json( 'POST', '/api/settings/' . $object->getIdentifier(), $form );

                    $response->assertJsonPath( 'status', 'success' );

                    foreach ( $form as $tab => $fields ) {
                        foreach ( $fields as $name => $value ) {
                            $value = ns()->option->get( $name );

                            if ( ! is_array( $value ) ) {
                                $this->assertTrue(
                                    ns()->option->get( $name ) == $value,
                                    sprintf(
                                        'Failed to assert that "%s" option has as value %s. Current value: %s',
                                        $name,
                                        $value,
                                        ns()->option->get( $name )
                                    )
                                );
                            }
                        }
                    }
                }
            } );
    }
}
