<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class CrudEditEntitiesTest extends TestCase
{
    use WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_edit_crud_form()
    {
        $this->attemptAuthenticate();

        collect( Storage::disk( 'ns' )->files( 'app/Crud' ) )
            ->map( function ( $fileName ) {
                $fileName = collect( explode( '/', $fileName ) );
                $file = pathinfo( $fileName->last() );

                return 'App\\Crud\\' . $file[ 'filename' ];
            } )
            ->each( function ( $class ) {
                $object = new $class;

                if ( ! empty( $object->getNamespace() ) ) {
                    $response = $this
                        ->withSession( $this->app[ 'session' ]->all() )
                        ->json( 'GET', '/api/crud/' . $object->getNamespace() . '/form-config' );

                    $response->assertOk();
                }
            } );
    }
}
