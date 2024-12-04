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
    public function test_crud_components()
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
                    'permissions' => [ 'read' ],
                ], [
                    'slug' => 'crud/{namespace}/columns',
                    'verb' => 'get',
                    'permissions' => [ 'read' ],
                ], [
                    'slug' => 'crud/{namespace}/config/{id?}',
                    'verb' => 'get',
                    'permissions' => [ 'read' ],
                ], [
                    'slug' => 'crud/{namespace}/form-config/{id?}',
                    'verb' => 'get',
                    'permissions' => [ 'create', 'update' ],
                ], [
                    'slug' => 'crud/{namespace}/export',
                    'verb' => 'post',
                    'permissions' => [ 'read' ],
                ], [
                    'slug' => 'crud/{namespace}/bulk-actions',
                    'verb' => 'post',
                    'permissions' => [ 'update' ],
                ], [
                    'slug' => 'crud/{namespace}/can-access',
                    'verb' => 'post',
                    'permissions' => [ 'read' ],
                ], [
                    'slug' => 'crud/{namespace}/{id}',
                    'verb' => 'delete',
                    'permissions' => [ 'delete' ],
                ],
            ];

            foreach ( $apiRoutes as $config ) {

                /**
                 * Check if the user has the necessary permissions
                 * to proceed with the test.
                 */
                foreach ( $config[ 'permissions' ] as $permission ) {
                    if ( ! ns()->allowedTo( $object->getPermission( $permission ) ) ) {
                        continue;
                    }
                }

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
