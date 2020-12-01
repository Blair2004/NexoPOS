<?php

namespace App\Providers;

use App\Listeners\CoreEventSubscriber;
use App\Listeners\CustomerEventSubscriber;
use App\Listeners\ExpensesEventSubscriber;
use App\Listeners\OrderEventsSubscriber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Listeners\ProcurementEventsSubscriber;
use App\Listeners\ProductEventsSubscriber;
use App\Services\ModulesService;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        WebRouteLoadedEvent::class => [

        ]
    ];

    protected $subscribe    =   [
        ProcurementEventsSubscriber::class,
        ProductEventsSubscriber::class,
        OrderEventsSubscriber::class,
        ExpensesEventSubscriber::class,
        CoreEventSubscriber::class,
        CustomerEventSubscriber::class,
    ];

    public function register()
    {
        parent::register();
    }

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * @var ModulesService
         */
        $modules    =   app()->make( ModulesService::class );

        collect( $modules->getEnabled() )
            ->each( fn( $module ) => $modules->triggerServiceProviders( $module, 'register', self::class ) );

        parent::boot();

        /**
         * @param ModulesService
         */
        $modules    =   app()->make( ModulesService::class );

        collect( $modules->getEnabled() )
            ->each( fn( $module ) => $modules->serviceProvider( $module, 'boot', self::class ) );
    }

    public function shouldDiscoverEvents()
    {
        return true;
    }
}
