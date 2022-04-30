<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Role;
use App\Models\Option;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;


class SaveSettingsTest extends TestCase
{
    use WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testSaveSettings()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );        

        collect( Storage::disk( 'ns' )->files( 'app/Settings' ) )
            ->map( function( $fileName ) {
                $fileName       =   collect( explode( '/', $fileName ) );
                $file           =   pathinfo( $fileName->last() );
                return 'App\\Settings\\' . $file[ 'filename' ];
            })
            ->each( function( $class ) {
                $object     =   new $class;

                $form       =   collect( $object->getForm()[ 'tabs' ] )->mapWithKeys( function( $value, $key ) {
                    return [
                        $key    =>  collect( $value[ 'fields' ] )
                            ->mapWithKeys( function( $field ) {
                            if ( $field[ 'name' ] === 'ns_store_language' ) {
                                return [ 
                                    $field[ 'name' ]    =   'en'
                                ]; // the site should always be in english for the tests.
                            } else {
                                return [
                                    $field[ 'name' ]    =>  match( $field[ 'type' ] ) {
                                        'text', 'textarea'      =>  strstr( $field[ 'name' ], 'email' ) ? $this->faker->email() : $this->faker->text(20),
                                        'select'                =>  ! empty( $field[ 'options' ] ) ? collect( $field[ 'options' ] )->random()[ 'value' ] : '',
                                        default                 =>  $field[ 'value' ]
                                    }
                                ];
                            }
                        })
                    ];
                })->toArray();

                if ( ! empty( $object->getNamespace() ) ) {
                    $response   =   $this
                        ->withSession( $this->app[ 'session' ]->all() )
                        ->json( 'POST', '/api/nexopos/v4/settings/' . $object->getNamespace(), $form );
                    
                    $response->assertJsonPath( 'status', 'success' );    
    
                    foreach( $form as $tab => $fields ) {
                        foreach( $fields as $name => $value ) {
                            $value  =   ns()->option->get( $name );

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
            });

    }
}
