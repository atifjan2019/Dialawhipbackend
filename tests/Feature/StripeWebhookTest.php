<?php

use App\Jobs\ProcessStripeWebhook;
use App\Models\StripeEvent;
use Illuminate\Support\Facades\Queue;

test('rejects request without signature', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);
    $this->postJson('/api/webhooks/stripe', ['id' => 'evt_1'])->assertStatus(400);
});

test('rejects request with bad signature', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);
    $this->withHeader('Stripe-Signature', 't=1,v1=bogus')
        ->postJson('/api/webhooks/stripe', ['id' => 'evt_1'])
        ->assertStatus(400);
});

function postStripeWebhook(string $payload): \Illuminate\Testing\TestResponse
{
    $timestamp = time();
    $signature = hash_hmac('sha256', "$timestamp.$payload", 'whsec_test');

    return test()->call(
        'POST',
        '/api/webhooks/stripe',
        [], [], [],
        [
            'HTTP_STRIPE_SIGNATURE' => "t=$timestamp,v1=$signature",
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload,
    );
}

test('deduplicates already-processed events by id', function () {
    Queue::fake();
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    StripeEvent::create([
        'id' => 'evt_dup',
        'type' => 'checkout.session.completed',
        'payload' => ['id' => 'evt_dup'],
        'processed_at' => now(),
    ]);

    $payload = '{"id":"evt_dup","type":"checkout.session.completed","data":{"object":{}}}';
    postStripeWebhook($payload)->assertOk()->assertJson(['status' => 'duplicate']);

    Queue::assertNotPushed(ProcessStripeWebhook::class);
});

test('new event is stored and queued for processing', function () {
    Queue::fake();
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $payload = '{"id":"evt_new","type":"checkout.session.completed","data":{"object":{}}}';
    postStripeWebhook($payload)->assertOk()->assertJson(['status' => 'queued']);

    expect(StripeEvent::find('evt_new'))->not->toBeNull();
    Queue::assertPushed(ProcessStripeWebhook::class);
});
