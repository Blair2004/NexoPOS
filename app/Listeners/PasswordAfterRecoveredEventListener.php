<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Events\PasswordAfterRecoveredEvent;
use App\Mail\PasswordRecoveredMail;

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
     * @param  object  $event
     * @return void
     */
    public function handle( PasswordAfterRecoveredEvent $event )
    {
        Mail::to( $event->user->email )
            ->queue( new PasswordRecoveredMail( $event->user ) );
    }
}
