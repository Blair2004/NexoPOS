<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $admin;

    public $options;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct( User $admin, User $user )
    {
        $this->admin    =   $admin;
        $this->user     =   $user;

        $this->options  =   app()->make( Options::class );
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->subject( $this->options->get( 'ns_notifications_registrations_administrator_email_title', __( '[NexoPOS] A New User Has Registered' ) ) )
            ->from( $this->options->get( 'ns_store_email', 'notifications@nexopos.com' ) )
            ->markdown('mails/user-registered-mail');
    }
}
