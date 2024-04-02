<?php

namespace App\Services;

use App\Classes\Hook;
use App\Models\User;
use App\Models\UserWidget;
use App\Widgets\BestCashiersWidget;
use App\Widgets\BestCustomersWidget;
use App\Widgets\ExpenseCardWidget;
use App\Widgets\IncompleteSaleCardWidget;
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

    public function __construct( private UsersService $usersService )
    {
        $this->widgets = Hook::filter( 'ns-dashboard-widgets', [
            IncompleteSaleCardWidget::class,
            ExpenseCardWidget::class,
            SaleCardWidget::class,
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
    public function getPermission(): string|bool
    {
        return $this->permission;
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
        $areas = [
            'first-column',
            'second-column',
            'third-column',
        ];

        $areaWidgets = [];

        $widgetClasses = collect( $this->widgets )->filter( function ( $class ) use ( $user ) {
            return ( new $class )->canAccess( $user );
        } )->toArray();

        /**
         * This will assign all widgets
         * to available areas.
         */
        foreach ( $widgetClasses as $index => $widgetClass ) {
            /**
             * @var WidgetService $widgetInstance
             */
            $widgetInstance = new $widgetClass;

            $areaWidgets[ $areas[ $index % 3 ] ][] = [
                'class-name' => $widgetClass,
                'component-name' => $widgetInstance->getVueComponent(),
            ];
        }

        /**
         * We're now storing widgets to
         * each relevant area.
         */
        foreach ( $areaWidgets as $areaName => $widgets ) {
            $config = [
                'column' => [
                    'name' => $areaName,
                    'widgets' => $widgets,
                ],
            ];

            $this->usersService->storeWidgetsOnAreas(
                config: $config,
                user: $user
            );
        }
    }

    /**
     * Initialize the widgets areas with their widgets.
     */
    public function bootWidgetsAreas(): void
    {
        $widgetArea = function () {
            return collect( [ 'first', 'second', 'third' ] )->map( function ( $column ) {
                $columnName = $column . '-column';

                return [
                    'name' => $columnName,
                    'widgets' => UserWidget::where( 'user_id', Auth::id() )
                        ->where( 'column', $columnName )
                        ->orderBy( 'position' )
                        ->get()
                        ->filter( fn( $widget ) => Gate::allows( ( new $widget->class_name )->getPermission() ) )
                        ->values(),
                ];
            } )->toArray();
        };

        $this->registerWidgetsArea( 'ns-dashboard-widgets', $widgetArea );
    }
}
