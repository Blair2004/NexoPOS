<?php

namespace Tests\Feature;

use App\Exceptions\NotAllowedException;
use App\Services\MarketplaceService;
use App\Services\ModulesService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceModuleInstallationTest extends TestCase
{
    public function test_manual_upload_remains_locked(): void
    {
        $originalLockValue = getenv( 'NS_LOCK_MODULES' );

        putenv( 'NS_LOCK_MODULES=true' );

        try {
            $this->expectException( NotAllowedException::class );

            app( ModulesService::class )->upload( UploadedFile::fake()->create( 'module.zip' ) );
        } finally {
            $originalLockValue === false
                ? putenv( 'NS_LOCK_MODULES' )
                : putenv( 'NS_LOCK_MODULES=' . $originalLockValue );
        }
    }

    public function test_marketplace_download_uses_the_trusted_installation_entry_point(): void
    {
        Storage::fake( 'ns-modules-temp' );
        Http::fake( [ '*' => Http::response( 'module archive', 200 ) ] );

        $expectedResult = [
            'status' => 'success',
        ];

        $moduleService = \Mockery::mock( ModulesService::class );
        $moduleService->shouldReceive( 'installFromMarketplace' )
            ->once()
            ->with( \Mockery::type( UploadedFile::class ) )
            ->andReturn( $expectedResult );

        $marketplaceService = \Mockery::mock( MarketplaceService::class, [ $moduleService ] )->makePartial();
        $marketplaceService->shouldReceive( 'authenticateRequest' )->once();

        $result = $marketplaceService->downloadModule( 10, 'license-id' );

        $this->assertSame( $expectedResult, $result );
    }
}
