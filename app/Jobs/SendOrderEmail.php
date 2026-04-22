<?php

namespace App\Jobs;

use App\Mail\OrderStatusMail;
use App\Models\Message;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public string $orderId, public string $templateKey) {}

    public function handle(): void
    {
        $order = Order::with(['customer', 'items', 'address'])->find($this->orderId);
        if (! $order || ! $order->customer?->email) {
            return;
        }

        $message = Message::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'channel' => Message::CHANNEL_EMAIL,
            'direction' => 'outbound',
            'template_key' => $this->templateKey,
            'content' => "Email: $this->templateKey for {$order->reference}",
            'status' => Message::STATUS_QUEUED,
        ]);

        Mail::to($order->customer->email)->send(new OrderStatusMail($order, $this->templateKey));

        $message->update(['status' => Message::STATUS_SENT, 'sent_at' => now()]);
    }
}
