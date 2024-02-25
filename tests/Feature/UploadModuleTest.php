<?php

namespace Tests\Feature;

use App\Services\ModulesService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class UploadModuleTest extends TestCase
{
    use WithAuthentication, WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_module_system()
    {
        $this->attemptAuthenticate();

        /**
         * Step 1: Generate the module
         *
         * @var ModulesService
         */
        $moduleService = app()->make( ModulesService::class );

        $name = str_replace( '.', '', $this->faker->text( 10 ) );
        $config = [
            'namespace' => ucwords( Str::camel( $name ) ),
            'name' => $name,
            'author' => 'NexoPOS',
            'description' => 'Generated from a test',
            'version' => '1.0',
        ];

        $moduleService->generateModule( $config );

        /**
         * Step 2: Test if the module was created
         */
        $moduleService->load();
        $module = $moduleService->get( $config[ 'namespace' ] );

        $this->assertTrue( $module[ 'namespace' ] === $config[ 'namespace' ], 'The module as created' );

        /**
         * Step 3: We'll zip the module
         * and reupload that once we've finish the tests
         */
        $result = $moduleService->extract( $config[ 'namespace' ] );

        /**
         * Step 4 : We'll force generate the module
         * but with a different description
         */
        $config[ 'description' ] = 'Changed description';
        $config[ 'force' ] = true;

        $moduleService->generateModule( $config );
        $moduleService->load();

        $module = $moduleService->get( $config[ 'namespace' ] );

        $this->assertTrue( $module[ 'description' ] === $config[ 'description' ], 'The force created module wasn\'t effective' );

        /**
         * Step 5 : We'll delete the generated module
         */
        $moduleService->delete( $config[ 'namespace' ] );
        $moduleService->load();

        $module = $moduleService->get( $config[ 'namespace' ] );

        $this->assertTrue( $module === false, 'The module wasn\'t deleted' );

        /**
         * Step 6: We'll reupload the module
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', '/api/modules', [
                'module' => UploadedFile::fake()->createWithContent( 'module.zip', file_get_contents( $result[ 'path' ] ) ),
            ] );

        $response->assertRedirect( ns()->route( 'ns.dashboard.modules-list' ) );

        /**
         * Step 7 : We'll re-delete the uploaded module
         */
        $moduleService->delete( $config[ 'namespace' ] );
        $moduleService->load();
        $module = $moduleService->get( $config[ 'namespace' ] );

        $this->assertTrue( $module === false, 'The uploaded module wasn\'t deleted' );
    }
}
