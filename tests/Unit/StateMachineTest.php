<?php

use App\Domain\Orders\StateMachine;
use App\Models\Order;

describe('StateMachine::allowed', function () {

    test('pending can go to confirmed or cancelled', function () {
        expect(StateMachine::allowed(Order::STATUS_PENDING, Order::STATUS_CONFIRMED))->toBeTrue()
            ->and(StateMachine::allowed(Order::STATUS_PENDING, Order::STATUS_CANCELLED))->toBeTrue()
            ->and(StateMachine::allowed(Order::STATUS_PENDING, Order::STATUS_DELIVERED))->toBeFalse();
    });

    test('out_for_delivery can recover through failed', function () {
        expect(StateMachine::allowed(Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_FAILED))->toBeTrue()
            ->and(StateMachine::allowed(Order::STATUS_FAILED, Order::STATUS_OUT_FOR_DELIVERY))->toBeTrue();
    });

    test('delivered can only be refunded', function () {
        expect(StateMachine::allowed(Order::STATUS_DELIVERED, Order::STATUS_REFUNDED))->toBeTrue();
        foreach ([Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED, Order::STATUS_FAILED] as $target) {
            expect(StateMachine::allowed(Order::STATUS_DELIVERED, $target))->toBeFalse();
        }
    });

    test('terminal states have no outgoing transitions', function () {
        expect(StateMachine::isTerminal(Order::STATUS_CANCELLED))->toBeTrue()
            ->and(StateMachine::isTerminal(Order::STATUS_REFUNDED))->toBeTrue()
            ->and(StateMachine::isTerminal(Order::STATUS_DELIVERED))->toBeFalse();
    });

    test('cannot skip stages', function () {
        expect(StateMachine::allowed(Order::STATUS_PENDING, Order::STATUS_IN_PREP))->toBeFalse()
            ->and(StateMachine::allowed(Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED))->toBeFalse()
            ->and(StateMachine::allowed(Order::STATUS_IN_PREP, Order::STATUS_DELIVERED))->toBeFalse();
    });
});
