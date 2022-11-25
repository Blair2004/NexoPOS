<?php
namespace App\Providers;

use App\Classes\Hook;
use App\Models\UserWidget;
use App\Services\WidgetService;
use App\Widgets\BestCashiersWidget;
use App\Widgets\BestCustomersWidget;
use App\Widgets\OrdersChartWidget;
use App\Widgets\OrdersSummaryWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class WidgetsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /**
         * @var WidgetService $widgetService
         */
        $widgetService  = app()->make( WidgetService::class );

        $widgetService->registerWidgets([
            OrdersSummaryWidget::class,
            BestCashiersWidget::class,
            BestCustomersWidget::class,
            OrdersChartWidget::class,
        ]);

        $widgetArea     =   fn() => ( collect([ 'first', 'second', 'third' ])->map( function( $column ) {
            $columnName =   $column . '-column';
            return [
                'name'  =>  $columnName,
                'widgets'   =>  UserWidget::where( 'user_id', Auth::id() )
                    ->where( 'column', $columnName )
                    ->orderBy( 'position' )
                    ->get()
            ];
        })->toArray() ) ;

        $widgetService->registerWidgetsArea( 'ns-dashboard-widgets', fn() => $widgetArea() );
    }
}