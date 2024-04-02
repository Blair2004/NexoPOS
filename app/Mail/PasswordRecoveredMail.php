<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordRecoveredMail extends Mailable
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
            ->from( ns()->option->get( 'ns_store_email', 'contact@nexopos.com' ), ns()->option->get( 'ns_store_name', env( 'APP_NAME' ) ) )
            ->to( $this->user->email )
            ->markdown( 'mails/password-recovered-mail' );
    }
}
