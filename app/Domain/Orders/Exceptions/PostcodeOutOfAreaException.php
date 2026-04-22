<?php

namespace App\Domain\Orders\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class PostcodeOutOfAreaException extends HttpException
{
    public function __construct(string $postcode)
    {
        parent::__construct(422, "Postcode '$postcode' is outside our delivery area.");
    }
}
