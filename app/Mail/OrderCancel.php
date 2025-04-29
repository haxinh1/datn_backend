<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCancel extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    /**
     * Create a new message instance.
     */
    public function __construct($order )
    {
        $this->order = $order;

    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Thông báo hủy đơn hàng')
                    ->view('emails.order-cancel')
                    ->with([
                       'order' => $this->order,
                  
                   
                    ]);
    }
}