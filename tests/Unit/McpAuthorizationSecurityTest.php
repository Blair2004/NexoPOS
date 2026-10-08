<?php

namespace Tests\Unit;

use App\Mcp\Resources\AuthorizedResource;
use App\Mcp\Resources\StoreConfigResource;
use App\Mcp\Servers\POSServer;
use App\Mcp\Tools\AuthorizedTool;
use App\Mcp\Tools\UpdateSettingsTool;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class McpAuthorizationSecurityTest extends TestCase
{
    public function test_tool_registration_requires_its_nexopos_permission(): void
    {
        $user = Mockery::mock( User::class );
        $user->shouldReceive( 'allowedTo' )
            ->once()
            ->with( [ 'manage.options' ] )
            ->andReturnFalse();
        $request = Mockery::mock( Request::class );
        $request->shouldReceive( 'user' )->once()->andReturn( $user );

        $this->assertFalse( ( new UpdateSettingsTool )->shouldRegister( $request ) );
    }

    public function test_resource_registration_requires_its_nexopos_permission(): void
    {
        $user = Mockery::mock( User::class );
        $user->shouldReceive( 'allowedTo' )
            ->once()
            ->with( [ 'manage.options' ] )
            ->andReturnTrue();
        $request = Mockery::mock( Request::class );
        $request->shouldReceive( 'user' )->once()->andReturn( $user );

        $this->assertTrue( ( new StoreConfigResource )->shouldRegister( $request ) );
    }

    public function test_every_registered_pos_capability_is_authorized(): void
    {
        $properties = ( new ReflectionClass( POSServer::class ) )->getDefaultProperties();

        foreach ( $properties[ 'tools' ] as $tool ) {
            $this->assertTrue( is_subclass_of( $tool, AuthorizedTool::class ), $tool );
        }

        foreach ( $properties[ 'resources' ] as $resource ) {
            $this->assertTrue( is_subclass_of( $resource, AuthorizedResource::class ), $resource );
        }
    }

    public function test_pos_server_requires_mcp_token_ability(): void
    {
        $route = collect( app( 'router' )->getRoutes() )
            ->first( fn( $route ) => $route->uri() === 'mcp/pos' );

        $this->assertNotNull( $route );
        $this->assertContains(
            CheckAbilities::class . ':mcp:use',
            $route->gatherMiddleware()
        );
    }

    public function test_upload_media_no_longer_accepts_server_file_paths(): void
    {
        $source = file_get_contents( app_path( 'Mcp/Tools/UploadMediaTool.php' ) );

        $this->assertStringNotContainsString( "'file_path'", $source );
    }
}
