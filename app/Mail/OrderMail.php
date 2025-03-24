<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
   public $order;

    public function __construct($order)
    {
     $this->order = $order;
    }

    /**
     * Get the message envelope.
     */

     public function build()
     {
         return $this->subject('Thông tin đơn hàng')
                     ->view('emails.order-mail');
                    //  ->with([
              
                    //      'order' => $this->order,
                    //  ]);
     }

    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Order Mail',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    // /**
    //  * Get the attachments for the message.
    //  *
    //  * @return array<int, \Illuminate\Mail\Mailables\Attachment>
    //  */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
