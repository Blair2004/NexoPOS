<?php
namespace App\Providers;

use App\Classes\Guide;
use App\Guides\DashboardGuide;
use App\Guides\ProcurementGuide;
use App\Guides\ProductGuide;
use App\Guides\RewardGuide;
use App\Services\GuideService;
use Illuminate\Support\ServiceProvider;

class GuideServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $guideService   =   app()->make( GuideService::class );

        $this->setMainGuide( $guideService );
    }

    private function setMainGuide( GuideService $guideService )
    {
        DashboardGuide::init( $guideService );
        ProcurementGuide::init( $guideService );
        ProductGuide::init( $guideService );
        RewardGuide::init( $guideService );
    }
}