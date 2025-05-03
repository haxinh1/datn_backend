<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\OrderCancel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderRefund extends Mailable
{
    use Queueable, SerializesModels;

    public $orderCancel;
    public $order;

    /**
     * Create a new message instance.
     */
    public function __construct(OrderCancel $orderCancel, Order $order)
    {
        $this->orderCancel = $orderCancel;
        $this->order = $order;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Xác nhận hoàn tiền đơn hàng')
                    ->view('emails.order-refund')
                    ->with([
                        'orderCode' => $this->order->code,
                        'refundProof' => $this->orderCancel->refund_proof,
                        'bankAccount' => $this->orderCancel->bank_account_number,
                        'bankName' => $this->orderCancel->bank_name,
                        'totalAmount' => $this->order->total_amount,
                    ]);
    }
}