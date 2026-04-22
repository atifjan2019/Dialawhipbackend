<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeWebhook;
use App\Models\StripeEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('Stripe-Signature');
        $secret = (string) config('services.stripe.webhook_secret');

        if (! $signature || ! $secret) {
            abort(400, 'Missing signature');
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), $signature, $secret);
        } catch (SignatureVerificationException $e) {
            abort(400, 'Invalid signature');
        }

        $existing = StripeEvent::find($event->id);
        if ($existing && $existing->processed_at) {
            return response()->json(['status' => 'duplicate']);
        }

        StripeEvent::updateOrCreate(
            ['id' => $event->id],
            ['type' => $event->type, 'payload' => $event->toArray()],
        );

        ProcessStripeWebhook::dispatch($event->id)->onQueue('webhooks');

        return response()->json(['status' => 'queued']);
    }
}
