<?php

namespace App\Support;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(public int $pence)
    {
        if ($pence < 0) {
            throw new InvalidArgumentException("Money cannot be negative: $pence");
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromPence(int $pence): self
    {
        return new self($pence);
    }

    public function plus(Money $other): self
    {
        return new self($this->pence + $other->pence);
    }

    public function times(int $factor): self
    {
        return new self($this->pence * $factor);
    }

    public function toPounds(): string
    {
        return number_format($this->pence / 100, 2, '.', '');
    }

    public function format(): string
    {
        return '£'.$this->toPounds();
    }
}
