<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $options;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct( User $user )
    {
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
            ->subject( $this->options->get( 'ns_notifications_registrations_user_email_title', __( '[NexoPOS] Your Account Has Been Created' ) ) )
            ->from( $this->options->get( 'ns_store_email', 'notifications@nexopos.com' ) )
            ->markdown('mails/welcome-mail');
    }
}
