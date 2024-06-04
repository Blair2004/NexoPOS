<?php
namespace Tests\NewFeature;

use App\Services\Helper;
use App\Services\ResetService;
use App\Services\SetupService;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class SetupAppTest extends TestCase
{
    use WithAuthentication;

    public function testConfigureApp()
    {
        $this->assertFalse( Helper::installed() );
        
        $resetService   =   app()->make( ResetService::class );
        $resetService->hardReset();

        /**
         * @var SetupService $setupService
         */
        $response   =   $this->get( '/dashboard' );
        $response->assertRedirectToRoute( 'ns.do-setup' );
    }
}