<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

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
    private array $widgets    =   [];

    /**
     * anyone can see the widget
     * by default.
     */
    protected $permission   =   false;

    /**
     * here is stored the widget ares.
     */
    protected $widgetAreas  =   [];

    /**
     * Describe what the widget does.
     */
    protected $description;

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
    public function canAccess(): bool
    {
        return ! $this->permission  ?: User::allowedTo( $this->permission );
    }

    /**
     * returns all defined widgets
     * without applying any restriction
     */
    public function getAllWidgets(): Collection
    {
        return collect( $this->widgets )->map( function( $widget ) {
            /**
             * @var WidgetService $widgetInstance
             */
            $widgetInstance     =   new $widget;

            return ( object ) [
                'className' =>  $widget,
                'instance'  =>  $widgetInstance,
                'name'      =>  $widgetInstance->getName(),
                'component' =>  $widgetInstance->getVueComponent(),
                'canAccess' =>  $widgetInstance->canAccess()
            ];
        });
    }

    /**
     * Build the collection of all declared widget
     * and check if the logged user is eligible to see them.
     */
    public function getWidgets(): Collection
    {
        return $this->getAllWidgets()
            ->filter( function( $widget ) {
                return $widget->canAccess;
            });
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
            $this->widgets[]    =   $widget;
        } else {
            foreach( $widget as $_widget ) {
                $this->registerWidgets( $_widget );
            }
        }
    }

    public function registerWidgetsArea( $name, $columns )
    {
        $this->widgetAreas[ $name ]     =   $columns;
    }

    public function getWidgetsArea( $name )
    {
        $widgets    =   $this->widgetAreas[ $name ] ?? [];

        if ( ! empty( $widgets() ) ) {
            return collect( $widgets() )->map( function( $widget ) use ( $name ) {
                return array_merge( $widget, [
                    'parent'    =>  $name
                ]);
            });
        }

        return collect([]);
    }
}