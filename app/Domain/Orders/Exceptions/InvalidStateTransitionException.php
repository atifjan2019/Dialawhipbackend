<?php

namespace App\Domain\Orders\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidStateTransitionException extends HttpException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct(422, "Cannot transition order from '$from' to '$to'.");
    }
}
