<{{ '?php' }}
namespace Modules\{{ $module[ 'namespace' ] }}\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class {{ $name }} extends Mailable
{
    use Queueable, SerializesModels;

    public function header()
    {
        return new Headers(
            // ...
        );
    }

    /**
    * Get the message envelope.
    *
    * @return \Illuminate\Mail\Mailables\Envelope
    */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Title',
            tags: [],
            metadata: [
                // ...
            ],
        );
    }
 
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: '{{ $module[ 'namespace' ] }}::mails.welcome'
        );
    }
}