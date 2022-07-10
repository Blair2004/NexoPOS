<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ActivateYourAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct( User $user )
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject( ns()->option->get( 'ns_notifications_registrations_user_activate_title', __( '[NexoPOS] Activate Your Account' ) ) )
            ->from( ns()->option->get( 'ns_store_email', 'notifications@nexopos.com' ) )
            ->markdown( 'mails/activate-your-account-mail' );
    }
}
