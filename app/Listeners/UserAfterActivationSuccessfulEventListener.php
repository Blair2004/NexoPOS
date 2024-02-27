<?php

namespace App\Listeners;

use App\Events\UserAfterActivationSuccessfulEvent;
use App\Services\WidgetService;

class UserAfterActivationSuccessfulEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        private WidgetService $widgetService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle( UserAfterActivationSuccessfulEvent $event )
    {
        /**
         * For every user who's activated, we will assign
         * default widget to their account.
         */
        $this->widgetService->addDefaultWidgetsToAreas( $event->user );
    }
}
