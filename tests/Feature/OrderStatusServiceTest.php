<?php

use App\Domain\Orders\Events\OrderStatusChanged;
use App\Domain\Orders\Exceptions\InvalidStateTransitionException;
use App\Domain\Orders\Services\OrderStatusService;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function makeOrder(array $attrs = []): Order
{
    $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
    return Order::create(array_merge([
        'reference' => 'CAT-TEST-'.rand(1, 9999),
        'customer_id' => $customer->id,
        'status' => Order::STATUS_PENDING,
        'subtotal_pence' => 3000, 'delivery_fee_pence' => 500, 'vat_pence' => 0, 'total_pence' => 3500,
    ], $attrs));
}

test('valid transition writes event and fires OrderStatusChanged', function () {
    Event::fake([OrderStatusChanged::class]);
    $order = makeOrder();

    app(OrderStatusService::class)->transition($order, Order::STATUS_CONFIRMED);

    expect($order->fresh()->status)->toBe(Order::STATUS_CONFIRMED);
    expect(OrderEvent::where('order_id', $order->id)->count())->toBe(1);
    Event::assertDispatched(OrderStatusChanged::class);
});

test('invalid transition throws 422 and does not fire event', function () {
    Event::fake([OrderStatusChanged::class]);
    $order = makeOrder();

    expect(fn () => app(OrderStatusService::class)->transition($order, Order::STATUS_DELIVERED))
        ->toThrow(InvalidStateTransitionException::class);

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING);
    Event::assertNotDispatched(OrderStatusChanged::class);
});

test('transition from terminal state is rejected', function () {
    $order = makeOrder(['status' => Order::STATUS_CANCELLED]);

    expect(fn () => app(OrderStatusService::class)->transition($order, Order::STATUS_CONFIRMED))
        ->toThrow(InvalidStateTransitionException::class);
});
