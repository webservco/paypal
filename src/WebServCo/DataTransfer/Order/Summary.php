<?php

declare(strict_types=1);

namespace WebServCo\DataTransfer\Order;

final readonly class Summary
{
    public function __construct(public float $total, public string $currency)
    {
    }
}
