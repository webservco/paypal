<?php

declare(strict_types=1);

namespace WebServCo\DataTransfer\Order;

final class Summary
{
    public function __construct(public readonly float $total, public readonly string $currency)
    {
    }
}
