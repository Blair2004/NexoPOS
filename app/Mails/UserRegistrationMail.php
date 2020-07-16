<?php
namespace App\Mails;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var User
     * host the new user object
     */
    private $user;

    /**
     * @var string
     * host the link to access tot he dashboard list
     */
    private $link;


    public function __construct( $data )
    {
        $this->user     =   $data[ 'user' ];
        $this->link     =   $data[ 'link' ];
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
            ->subject( __( '🎉 A new user has registered' ) )
            ->markdown('tendoo::email.new-user', [
                'link'  =>  $this->link,
                'user'  =>  $this->user
            ]);
    }
}