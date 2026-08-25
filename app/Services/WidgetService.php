<?php

namespace App\Services;

use App\Classes\Hook;
use App\Models\User;
use App\Models\UserWidget;
use App\Widgets\BestCashiersWidget;
use App\Widgets\BestCustomersWidget;
use App\Widgets\ExpenseCardWidget;
use App\Widgets\IncompleteSaleCardWidget;
use App\Widgets\MyNexoPosWidget;
use App\Widgets\OrdersChartWidget;
use App\Widgets\OrdersSummaryWidget;
use App\Widgets\ProfileWidget;
use App\Widgets\SaleCardWidget;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class WidgetService
{
    /**
     * Core widgets are arranged to fill complete grid rows on first install.
     * Module widgets retain their registration order after these defaults.
     *
     * @var array<int, class-string<WidgetService>>
     */
    protected const DEFAULT_WIDGET_ORDER = [
        IncompleteSaleCardWidget::class,
        ExpenseCardWidget::class,
        SaleCardWidget::class,
        MyNexoPosWidget::class,
        OrdersChartWidget::class,
        BestCustomersWidget::class,
        OrdersSummaryWidget::class,
        BestCashiersWidget::class,
        ProfileWidget::class,
    ];

    /**
     * The vue component name of the component
     * is registered on this property.
     */
    protected $vueComponent;

    /**
     * the current widget name
     * is registered here
     */
    protected string $name;

    /**
     * All declared widgets are
     * registered on this parameter
     */
    private array $widgets = [];

    /**
     * anyone can see the widget
     * by default.
     */
    protected $permission = false;

    /**
     * here is stored the widget ares.
     */
    protected $widgetAreas = [];

    /**
     * Describe what the widget does.
     */
    protected $description;

    /**
     * The widget footprint expressed as columns x rows.
     */
    protected string $layout = '1x1';

    /**
     * The resize policy: strict, restricted, or unrestricted.
     */
    protected string $layoutPolicy = 'strict';

    /**
     * Layouts accepted when the resize policy is restricted.
     *
     * @var array<int, string>
     */
    protected array $supportedLayouts = [];

    public function __construct( private UsersService $usersService )
    {
        $this->widgets = Hook::filter( 'ns-dashboard-widgets', [
            IncompleteSaleCardWidget::class,
            ExpenseCardWidget::class,
            SaleCardWidget::class,
            MyNexoPosWidget::class,
            BestCustomersWidget::class,
            ProfileWidget::class,
            OrdersChartWidget::class,
            OrdersSummaryWidget::class,
            BestCashiersWidget::class,
        ] );
    }

    /**
     * Returns the widget vue component name
     */
    public function getVueComponent(): string
    {
        return $this->vueComponent;
    }

    /**
     * Return the widget name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return a boolean if the logged user
     * is allowed to see the current widget
     */
    public function canAccess( ?User $user = null ): bool
    {
        return ! $this->permission ?: ( $user == null ? Gate::allows( $this->permission ) : Gate::forUser( $user )->allows( $this->permission ) );
    }

    /**
     * returns all defined widgets
     * without applying any restriction
     */
    public function getAllWidgets(): Collection
    {
        return collect( $this->widgets )->map( function ( $widget ) {
            /**
             * @var WidgetService $widgetInstance
             */
            $widgetInstance = new $widget;

            return (object) [
                'class-name' => $widget,
                'instance' => $widgetInstance,
                'name' => $widgetInstance->getName(),
                'component-name' => $widgetInstance->getVueComponent(),
                'canAccess' => $widgetInstance->canAccess(),
                'data' => $widgetInstance->getData(),
                'layout' => $widgetInstance->getLayout(),
                'supported-layouts' => $widgetInstance->getSupportedLayouts(),
                'layout-policy' => $widgetInstance->getLayoutPolicy(),
            ];
        } );
    }

    /**
     * Build the collection of all declared widget
     * and check if the logged user is eligible to see them.
     */
    public function getWidgets(): Collection
    {
        return $this->getAllWidgets()
            ->filter( function ( $widget ) {
                return $widget->canAccess;
            } );
    }

    /**
     * Returns only the declared perimssion. If
     * not defined, will return false.
     */
    public function getData(): array
    {
        return [];
    }

    public function getPermission(): string|bool
    {
        return $this->permission;
    }

    /**
     * Return a normalized widget footprint.
     *
     * @return array{name: string, columns: int, rows: int}
     */
    public function getLayout(): array
    {
        return $this->normalizeLayout( $this->layout );
    }

    public function getLayoutPolicy(): string
    {
        return in_array( $this->layoutPolicy, [ 'strict', 'restricted', 'unrestricted' ], true )
            ? $this->layoutPolicy
            : 'strict';
    }

    /**
     * Return the layouts a user may select for this widget.
     *
     * @return array<int, array{name: string, columns: int, rows: int}>
     */
    public function getSupportedLayouts(): array
    {
        $suggestedLayout = $this->getLayout();
        $layoutPolicy = $this->getLayoutPolicy();

        if ( $layoutPolicy === 'strict' ) {
            return [ $suggestedLayout ];
        }

        $layouts = $layoutPolicy === 'unrestricted'
            ? collect( range( 1, 3 ) )->flatMap( fn( int $columns ) => collect( range( 1, 5 ) )
                ->map( fn( int $rows ) => "{$columns}x{$rows}" ) )
            : collect( $this->supportedLayouts )->filter( fn( mixed $layout ) => is_string( $layout ) && $this->isValidLayout( $layout ) );

        return $layouts
            ->prepend( $suggestedLayout['name'] )
            ->unique()
            ->map( fn( string $layout ) => $this->normalizeLayout( $layout ) )
            ->values()
            ->all();
    }

    public function supportsLayout( string $layout ): bool
    {
        return collect( $this->getSupportedLayouts() )->contains( 'name', $layout );
    }

    /**
     * @return array{name: string, columns: int, rows: int}
     */
    private function normalizeLayout( string $layout ): array
    {
        if ( preg_match( '/^([1-3])x([1-5])$/', $layout, $matches ) !== 1 ) {
            return [
                'name' => '1x1',
                'columns' => 1,
                'rows' => 1,
            ];
        }

        return [
            'name' => $layout,
            'columns' => (int) $matches[1],
            'rows' => (int) $matches[2],
        ];
    }

    private function isValidLayout( string $layout ): bool
    {
        return preg_match( '/^([1-3])x([1-5])$/', $layout ) === 1;
    }

    /**
     * Returns the description of the widget.
     * That describe what the widget does.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Declare widgets classes that
     * should be registered
     */
    public function registerWidgets( string|array $widget ): void
    {
        if ( ! is_array( $widget ) ) {
            $this->widgets[] = $widget;
        } else {
            foreach ( $widget as $_widget ) {
                $this->registerWidgets( $_widget );
            }
        }
    }

    /**
     * Register widgets areas.
     */
    public function registerWidgetsArea( string $name, Closure $columns ): void
    {
        $this->widgetAreas[ $name ] = $columns;
    }

    /**
     * Get the widget defined for a specifc area.
     */
    public function getWidgetsArea( string $name ): Collection
    {
        $widgets = $this->widgetAreas[ $name ] ?? [];

        if ( ! empty( $widgets() ) ) {
            return collect( $widgets() )->map( function ( $widget ) use ( $name ) {
                return array_merge( $widget, [
                    'parent' => $name,
                ] );
            } );
        }

        return collect( [] );
    }

    /**
     * Will assign the widget to the provider user.
     */
    public function addDefaultWidgetsToAreas( User $user ): void
    {
        $widgets = $this->orderDefaultWidgetClasses( collect( $this->widgets ) )->filter( function ( $class ) use ( $user ) {
            return ( new $class )->canAccess( $user );
        } )->map( function ( string $widgetClass ): array {
            $widgetInstance = new $widgetClass;

            return [
                'class-name' => $widgetClass,
                'component-name' => $widgetInstance->getVueComponent(),
                'layout' => null,
            ];
        } )->values()->all();

        $this->usersService->storeWidgetLayout( $widgets, $user );
    }

    /**
     * Apply the fresh-install core order without reintroducing filtered widgets.
     *
     * @param  Collection<int, class-string<WidgetService>> $widgetClasses
     * @return Collection<int, class-string<WidgetService>>
     */
    protected function orderDefaultWidgetClasses( Collection $widgetClasses ): Collection
    {
        $widgetClasses = $widgetClasses->unique()->values();
        $coreWidgets = collect( self::DEFAULT_WIDGET_ORDER )
            ->filter( fn( string $widgetClass ): bool => $widgetClasses->containsStrict( $widgetClass ) );
        $additionalWidgets = $widgetClasses
            ->reject( fn( string $widgetClass ): bool => in_array( $widgetClass, self::DEFAULT_WIDGET_ORDER, true ) );

        return $coreWidgets->concat( $additionalWidgets )->values();
    }

    /**
     * Initialize the widgets areas with their widgets.
     */
    public function bootWidgetsAreas(): void
    {
        $widgetArea = function () {
            $registeredWidgets = collect( $this->widgets )->mapWithKeys( function ( string $widgetClass ): array {
                return [ ( new $widgetClass )->getVueComponent() => $widgetClass ];
            } );

            return [
                [
                    'name' => 'dashboard',
                    'widgets' => UserWidget::where( 'user_id', Auth::id() )
                        ->orderBy( 'position' )
                        ->get()
                        ->filter( function ( UserWidget $widget ) use ( $registeredWidgets ): bool {
                            $widgetClass = $registeredWidgets->get( $widget->identifier );

                            return $widgetClass !== null && ( new $widgetClass )->canAccess();
                        } )
                        ->values(),
                ],
            ];
        };

        $this->registerWidgetsArea( 'ns-dashboard-widgets', $widgetArea );
    }
}
