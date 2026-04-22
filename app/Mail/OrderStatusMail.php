<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $templateKey) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->templateKey) {
                'order_confirmed' => "Order confirmed — {$this->order->reference}",
                'out_for_delivery' => "On its way — {$this->order->reference}",
                'delivered' => "Delivered — {$this->order->reference}",
                'refunded' => "Refund processed — {$this->order->reference}",
                'cancelled' => "Order cancelled — {$this->order->reference}",
                default => "Order update — {$this->order->reference}",
            },
        );
    }

    public function content(): Content
    {
        return new Content(view: "emails.$this->templateKey", with: ['order' => $this->order]);
    }
}
