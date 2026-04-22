<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('customer cannot access admin routes', function () {
    Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_CUSTOMER]));

    $this->getJson('/api/v1/admin/orders')->assertForbidden();
});

test('driver cannot access admin routes', function () {
    Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_DRIVER]));

    $this->getJson('/api/v1/admin/orders')->assertForbidden();
});

test('customer cannot access driver routes', function () {
    Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_CUSTOMER]));

    $this->getJson('/api/v1/driver/deliveries')->assertForbidden();
});

test('staff can access admin orders', function () {
    Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));

    $this->getJson('/api/v1/admin/orders')->assertOk();
});

test('admin can access admin routes', function () {
    Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

    $this->getJson('/api/v1/admin/customers')->assertOk();
});

test('unauthenticated cannot access admin', function () {
    $this->getJson('/api/v1/admin/orders')->assertUnauthorized();
});
