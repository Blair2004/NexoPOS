<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UsersService;
use App\Services\WidgetService;
use App\Widgets\BestCashiersWidget;
use App\Widgets\BestCustomersWidget;
use App\Widgets\ExpenseCardWidget;
use App\Widgets\IncompleteSaleCardWidget;
use App\Widgets\MyNexoPosWidget;
use App\Widgets\OrdersChartWidget;
use App\Widgets\OrdersSummaryWidget;
use App\Widgets\ProfileWidget;
use App\Widgets\SaleCardWidget;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

class WidgetServiceTest extends TestCase
{
    public function test_fresh_user_receives_the_packed_default_order_with_suggested_layouts(): void
    {
        $user = new User;
        $user->id = 42;
        $userGate = Mockery::mock( GateContract::class );
        $userGate->shouldReceive( 'allows' )->andReturnTrue();
        Gate::shouldReceive( 'forUser' )->with( $user )->andReturn( $userGate );

        $usersService = Mockery::mock( UsersService::class );
        $usersService->shouldReceive( 'storeWidgetLayout' )
            ->once()
            ->withArgs( function ( array $widgets, $storedUser ) use ( $user ): bool {
                $this->assertSame( $user, $storedUser );
                $this->assertSame( [
                    'nsOrdersChart',
                    'nsBestCustomers',
                    'nsOrdersSummary',
                    'nsBestCashiers',
                    'nsProfileWidget',
                    'nsMyNexoPosWidget',
                    'nsIncompleteSaleCardWidget',
                    'nsExpenseCardWidget',
                    'nsSaleCardWidget',
                ], array_column( $widgets, 'component-name' ) );
                $this->assertSame( array_fill( 0, 9, null ), array_column( $widgets, 'layout' ) );

                return true;
            } )
            ->andReturn( [ 'status' => 'success' ] );

        ( new WidgetService( $usersService ) )->addDefaultWidgetsToAreas( $user );
    }

    public function test_default_widgets_are_ordered_to_fill_complete_grid_rows(): void
    {
        $widget = new class( app( UsersService::class ) ) extends WidgetService
        {
            public function orderWidgets( array $widgetClasses ): array
            {
                return $this->orderDefaultWidgetClasses( collect( $widgetClasses ) )->all();
            }
        };
        $moduleWidget = WidgetServiceTestModuleWidget::class;

        $orderedWidgets = $widget->orderWidgets( [
            IncompleteSaleCardWidget::class,
            ExpenseCardWidget::class,
            SaleCardWidget::class,
            $moduleWidget,
            MyNexoPosWidget::class,
            BestCustomersWidget::class,
            ProfileWidget::class,
            OrdersChartWidget::class,
            OrdersSummaryWidget::class,
            BestCashiersWidget::class,
        ] );

        $this->assertSame( [
            OrdersChartWidget::class,
            BestCustomersWidget::class,
            OrdersSummaryWidget::class,
            BestCashiersWidget::class,
            ProfileWidget::class,
            MyNexoPosWidget::class,
            IncompleteSaleCardWidget::class,
            ExpenseCardWidget::class,
            SaleCardWidget::class,
            $moduleWidget,
        ], $orderedWidgets );

        $this->assertSame(
            range( 0, 16 ),
            $this->occupiedGridCells( array_slice( $orderedWidgets, 0, 9 ) )
        );
    }

    /**
     * Simulate the dashboard's three-column dense placement.
     *
     * @param  array<int, class-string<WidgetService>> $widgetClasses
     * @return array<int, int>
     */
    private function occupiedGridCells( array $widgetClasses ): array
    {
        $occupiedCells = [];

        foreach ( $widgetClasses as $widgetClass ) {
            $layout = ( new $widgetClass )->getLayout();
            $hasBeenPlaced = false;

            for ( $row = 0; ! $hasBeenPlaced; $row++ ) {
                for ( $column = 0; $column <= 3 - $layout['columns']; $column++ ) {
                    $candidateCells = [];

                    for ( $rowOffset = 0; $rowOffset < $layout['rows']; $rowOffset++ ) {
                        for ( $columnOffset = 0; $columnOffset < $layout['columns']; $columnOffset++ ) {
                            $candidateCells[] = ( ( $row + $rowOffset ) * 3 ) + $column + $columnOffset;
                        }
                    }

                    if ( array_intersect( $candidateCells, array_keys( $occupiedCells ) ) !== [] ) {
                        continue;
                    }

                    foreach ( $candidateCells as $cell ) {
                        $occupiedCells[$cell] = true;
                    }

                    $hasBeenPlaced = true;
                    break;
                }
            }
        }

        $cells = array_keys( $occupiedCells );
        sort( $cells );

        return $cells;
    }

    /**
     * The dashboard exposes the My NexoPOS invite widget to eligible users.
     */
    public function test_widget_layouts_are_normalized(): void
    {
        $widget = new class( app( UsersService::class ) ) extends WidgetService
        {
            public function normalize( string $layout ): array
            {
                $this->layout = $layout;

                return $this->getLayout();
            }

            public function configurePolicy( string $policy, array $supportedLayouts = [] ): void
            {
                $this->layoutPolicy = $policy;
                $this->supportedLayouts = $supportedLayouts;
            }
        };

        foreach ( [ '1x1', '1x2', '1x3', '1x4', '1x5', '2x1', '2x2', '2x3', '2x4', '2x5', '3x1', '3x2', '3x3', '3x4', '3x5' ] as $layout ) {
            $normalized = $widget->normalize( $layout );

            $this->assertSame( $layout, $normalized['name' ] );
            $this->assertSame( (int) $layout[0], $normalized['columns' ] );
            $this->assertSame( (int) $layout[2], $normalized['rows' ] );
        }

        foreach ( [ '', '0x1', '1X1', '4x2', '2x6', 'wide' ] as $invalidLayout ) {
            $this->assertSame(
                [ 'name' => '1x1', 'columns' => 1, 'rows' => 1 ],
                $widget->normalize( $invalidLayout )
            );
        }

        $widget->normalize( '2x2' );
        $widget->configurePolicy( 'strict' );
        $this->assertSame( [ '2x2' ], array_column( $widget->getSupportedLayouts(), 'name' ) );

        $widget->configurePolicy( 'restricted', [ '1x5', 'invalid', '3x5', '1x5' ] );
        $this->assertSame( [ '2x2', '1x5', '3x5' ], array_column( $widget->getSupportedLayouts(), 'name' ) );

        $widget->configurePolicy( 'unrestricted' );
        $this->assertCount( 15, $widget->getSupportedLayouts() );
        $this->assertTrue( $widget->supportsLayout( '3x5' ) );
    }

    public function test_my_nexopos_widget_is_registered(): void
    {
        $widgets = app( WidgetService::class )->getAllWidgets();

        $widget = $widgets->firstWhere( 'class-name', MyNexoPosWidget::class );

        $this->assertNotNull( $widget );
        $this->assertSame( 'My NexoPOS', $widget->name );
        $this->assertSame( 'nsMyNexoPosWidget', $widget->{'component-name'} );
        $this->assertSame( 'manage.modules', $widget->instance->getPermission() );
        $this->assertIsArray( $widget->data );
        $this->assertArrayHasKey( 'isConnected', $widget->data );
        $this->assertIsBool( $widget->data[ 'isConnected' ] );
    }
}

class WidgetServiceTestModuleWidget extends WidgetService
{
    protected $vueComponent = 'widgetServiceTestModuleWidget';

    public function __construct()
    {
        $this->name = 'Module Widget';
        $this->description = 'Tests module widget ordering.';
    }
}
