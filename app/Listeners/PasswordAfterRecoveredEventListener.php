<?php

namespace App\Listeners;

use App\Events\PasswordAfterRecoveredEvent;
use App\Mail\PasswordRecoveredMail;
use Illuminate\Support\Facades\Mail;

class PasswordAfterRecoveredEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle( PasswordAfterRecoveredEvent $event )
    {
        Mail::to( $event->user->email )
            ->queue( new PasswordRecoveredMail( $event->user ) );
    }
}
