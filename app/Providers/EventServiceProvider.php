<?php

namespace App\Providers;

use App\Classes\Hook;
use App\Filters\MenusFilter;
use App\Listeners\CashRegisterEventsSubscriber;
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
    ];

    protected $subscribe    =   [];

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
         * if something doesn't prevent the subscribers to be registered
         * We'll then register them.
         */
        if ( Hook::filter( 'ns-register-subscribers', true ) ) {
            Event::subscribe( ProcurementEventsSubscriber::class );
            Event::subscribe( ProductEventsSubscriber::class );
            Event::subscribe( OrderEventsSubscriber::class );
            Event::subscribe( ExpensesEventSubscriber::class );
            Event::subscribe( CoreEventSubscriber::class );
            Event::subscribe( CustomerEventSubscriber::class );
            Event::subscribe( CashRegisterEventsSubscriber::class );
        }

        Hook::addFilter( 'ns-dashboard-menus', [ MenusFilter::class, 'injectRegisterMenus' ]);
    }

    public function shouldDiscoverEvents()
    {
        return true;
    }
}
