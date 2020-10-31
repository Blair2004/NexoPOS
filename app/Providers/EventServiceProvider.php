<?php

namespace App\Providers;

use App\Listeners\CoreEventSubscriber;
use App\Listeners\ExpensesEventSubscriber;
use App\Listeners\OrderEventsSubscriber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Listeners\ProcurementEventsSubscriber;
use App\Listeners\ProductEventsSubscriber;

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

    protected $subscribe    =   [
        ProcurementEventsSubscriber::class,
        ProductEventsSubscriber::class,
        OrderEventsSubscriber::class,
        ExpensesEventSubscriber::class,
        CoreEventSubscriber::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }

    public function shouldDiscoverEvents()
    {
        return true;
    }
}
