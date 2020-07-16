<?php
namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ActivateAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var
     * link
     */
    private $link;

    /**
     * @var 
     * user
     */
    private $user;

    /**
     * Send a mail notification to a user with the password refresh link.
     *
     * @return void
     */
    public function __construct( $link, $user )
    {
        $this->link     =   $link;
        $this->user     =   $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $options    =   app()->make( 'App\Services\Options' );
        return $this->from( $options->get( 'app_mail_from_address', 'notifications@tendoo.org' ) )
            ->subject( __( '👮 Activate Your Account !' ) )
            ->markdown('tendoo::email.activate-account', [
                'link'  =>  $this->link,
                'user'  =>  $this->user
            ]);
    }
}