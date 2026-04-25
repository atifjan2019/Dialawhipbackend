<?php

namespace App\Domain\Orders\Exceptions;

use RuntimeException;

/**
 * Thrown when a customer tries to price/place an order while the admin has
 * marked the shop as closed (`order.is_open` setting is false).
 */
class ShopClosedException extends RuntimeException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'The shop is not accepting orders right now.');
    }
}
