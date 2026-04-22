<?php

namespace App\Domain\Orders\Exceptions;

use App\Support\Money;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BelowMinimumOrderException extends HttpException
{
    public function __construct(Money $subtotal, Money $minimum)
    {
        parent::__construct(422, "Order subtotal {$subtotal->format()} is below the minimum of {$minimum->format()}.");
    }
}
