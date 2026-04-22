<?php

use App\Domain\Orders\Exceptions\BelowMinimumOrderException;
use App\Domain\Orders\Exceptions\PostcodeOutOfAreaException;
use App\Domain\Orders\Services\OrderPricingService;
use App\Models\Category;
use App\Models\Product;
use App\Models\ServiceArea;
use App\Models\Setting;

beforeEach(function () {
    Setting::put('order.minimum_pence', 1000);
    Setting::put('vat.rate_bps', 0);

    $this->category = Category::create([
        'name' => 'Test',
        'slug' => 'test',
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $this->product = Product::create([
        'category_id' => $this->category->id,
        'name' => 'Pasty', 'slug' => 'pasty',
        'price_pence' => 500, 'is_active' => true,
    ]);

    ServiceArea::create([
        'postcode_prefix' => 'NE1',
        'delivery_fee_pence' => 400,
        'is_active' => true,
    ]);
});

test('prices cart with quantity and delivery fee', function () {
    $result = app(OrderPricingService::class)->priceCart(
        [['product_id' => $this->product->id, 'quantity' => 3]],
        'NE1 4AB',
    );

    expect($result['subtotal']->pence)->toBe(1500);
    expect($result['delivery_fee']->pence)->toBe(400);
    expect($result['total']->pence)->toBe(1900);
});

test('rejects out-of-area postcode', function () {
    expect(fn () => app(OrderPricingService::class)->priceCart(
        [['product_id' => $this->product->id, 'quantity' => 3]],
        'SW1A 1AA',
    ))->toThrow(PostcodeOutOfAreaException::class);
});

test('rejects below-minimum subtotal', function () {
    Setting::put('order.minimum_pence', 5000);

    expect(fn () => app(OrderPricingService::class)->priceCart(
        [['product_id' => $this->product->id, 'quantity' => 1]],
        'NE1 4AB',
    ))->toThrow(BelowMinimumOrderException::class);
});

test('applies VAT when configured', function () {
    Setting::put('vat.rate_bps', 2000); // 20%

    $result = app(OrderPricingService::class)->priceCart(
        [['product_id' => $this->product->id, 'quantity' => 4]],
    );

    // subtotal 2000, vat 20% = 400
    expect($result['subtotal']->pence)->toBe(2000);
    expect($result['vat']->pence)->toBe(400);
    expect($result['total']->pence)->toBe(2400);
});

test('rejects unavailable product', function () {
    $inactive = Product::create([
        'category_id' => $this->category->id,
        'name' => 'Gone', 'slug' => 'gone',
        'price_pence' => 500, 'is_active' => false,
    ]);

    expect(fn () => app(OrderPricingService::class)->priceCart(
        [['product_id' => $inactive->id, 'quantity' => 3]],
    ))->toThrow(InvalidArgumentException::class);
});
