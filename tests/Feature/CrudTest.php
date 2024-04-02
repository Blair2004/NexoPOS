<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class CrudTest extends TestCase
{
    use WithAuthentication;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCrudComponents()
    {
        $this->attemptAuthenticate();

        $files = Storage::disk( 'ns' )->allFiles( 'app/Crud' );

        foreach ( $files as $file ) {
            $path = pathinfo( $file );
            $class = 'App\Crud\\' . $path[ 'filename' ];
            $object = new $class;
            $entries = $object->getEntries();

            $apiRoutes = [
                [
                    'slug' => 'crud/{namespace}',
                    'verb' => 'get',
                ], [
                    'slug' => 'crud/{namespace}/columns',
                    'verb' => 'get',
                ], [
                    'slug' => 'crud/{namespace}/config/{id?}',
                    'verb' => 'get',
                ], [
                    'slug' => 'crud/{namespace}/form-config/{id?}',
                    'verb' => 'get',
                ], [
                    'slug' => 'crud/{namespace}/export',
                    'verb' => 'post',
                ], [
                    'slug' => 'crud/{namespace}/bulk-actions',
                    'verb' => 'post',
                ], [
                    'slug' => 'crud/{namespace}/can-access',
                    'verb' => 'post',
                ], [
                    'slug' => 'crud/{namespace}/{id}',
                    'verb' => 'delete',
                ],
            ];

            foreach ( $apiRoutes as $config ) {
                if ( isset( $config[ 'slug' ] ) ) {
                    $slug = str_replace( '{namespace}', $object->getNamespace(), $config[ 'slug' ] );

                    /**
                     * In case we have an {id} on the slug
                     * we'll replace that with the existing id
                     */
                    if ( count( $entries[ 'data' ] ) > 0 ) {
                        $slug = str_replace( '{id}', $entries[ 'data' ][0]->{'$id'}, $slug );
                        $slug = str_replace( '{id?}', $entries[ 'data' ][0]->{'$id'}, $slug );
                    }

                    /**
                     * We shouldn't have any {id} or {id?} on
                     * the URL to prevent deleting CRUD with no records.
                     */
                    if ( preg_match( '/\{id\?\}/', $slug ) ) {
                        $response = $this
                            ->withSession( $this->app[ 'session' ]->all() )
                            ->json( strtoupper( $config[ 'verb' ] ), '/api/' . $slug, [
                                'entries' => [ 1 ],
                                'action' => 'unknown',
                            ] );

                        if ( $response->status() !== 200 ) {
                            $response->assertOk();
                        }
                    }
                }
            }

            $this->assertArrayHasKey( 'data', $entries, 'Crud Response' );
        }
    }
}
