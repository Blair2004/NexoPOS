<?php
namespace App\Providers;

use App\Classes\Hook;
use App\Models\User;
use App\Models\UserWidget;
use App\Services\WidgetService;
use App\Widgets\BestCashiersWidget;
use App\Widgets\BestCustomersWidget;
use App\Widgets\ExpenseCardWidget;
use App\Widgets\IncompleteSaleCardWidget;
use App\Widgets\OrdersChartWidget;
use App\Widgets\OrdersSummaryWidget;
use App\Widgets\ProfileWidget;
use App\Widgets\SaleCardWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WidgetsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /**
         * @var WidgetService $widgetService
         */
        $widgetService  = app()->make( WidgetService::class );

        $widgetService->bootWidgetsAreas();
    }
}