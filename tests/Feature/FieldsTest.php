<?php

namespace Tests\Feature;

use App\Services\FieldsService;
use App\Services\SettingsPage;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class FieldsTest extends TestCase
{
    use WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testFieldsAndForms()
    {
        $this->attemptAuthenticate();

        $files = Storage::disk( 'ns' )->files( 'app/Fields' );

        foreach ( $files as $file ) {
            $path = pathinfo( $file );
            $class = 'App\Fields\\' . $path[ 'filename' ];

            /**
             * @var FieldsService $object
             */
            $object = new $class;

            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'get', '/api/fields/' . $class::getIdentifier() );

            $result = collect( json_decode( $response->getContent() ) );

            if ( $result->filter( fn( $field ) => isset( $field->name ) )->count() !== $result->count() ) {
                $this->assertTrue(
                    $result->filter( fn( $field ) => isset( $field->name ) )->count() === $result->count(),
                    sprintf( 'Some fields aren\'t corretly defined for the class %s.', $class )
                );
            }
        }

        $files = Storage::disk( 'ns' )->files( 'app/Forms' );

        foreach ( $files as $file ) {
            $path = pathinfo( $file );
            $class = 'App\Forms\\' . $path[ 'filename' ];

            /**
             * @var SettingsPage $object
             */
            $object = new $class;

            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'get', '/api/forms/' . $object->getIdentifier() );

            $result = json_decode( $response->getContent() );

            $this->assertTrue(
                isset( $result->tabs ),
                sprintf(
                    'The form %s doesn\'t have tabs defined.',
                    $object->getIdentifier()
                )
            );
        }
    }
}
