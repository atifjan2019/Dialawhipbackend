<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Twilio\Rest\Client as TwilioClient;

class SendOrderSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public string $orderId, public string $templateKey) {}

    public function handle(): void
    {
        $order = Order::with('customer')->find($this->orderId);
        if (! $order || ! $order->customer?->phone) {
            return;
        }

        $content = trim((string) View::make("sms.$this->templateKey", ['order' => $order])->render());

        $message = Message::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'channel' => Message::CHANNEL_SMS,
            'direction' => 'outbound',
            'template_key' => $this->templateKey,
            'content' => $content,
            'status' => Message::STATUS_QUEUED,
        ]);

        $sid = config('services.twilio.account_sid');
        $token = config('services.twilio.auth_token');
        $messagingServiceSid = config('services.twilio.messaging_service_sid');

        if (! $sid || ! $token || ! $messagingServiceSid) {
            Log::warning('Twilio not configured — skipping SMS', ['order' => $order->reference]);
            $message->update(['status' => Message::STATUS_FAILED]);
            return;
        }

        $client = new TwilioClient($sid, $token);
        $response = $client->messages->create($order->customer->phone, [
            'messagingServiceSid' => $messagingServiceSid,
            'body' => $content,
        ]);

        $message->update([
            'provider_id' => $response->sid,
            'status' => Message::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }
}
