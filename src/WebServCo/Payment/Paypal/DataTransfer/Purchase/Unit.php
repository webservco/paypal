<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer\Purchase;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * Unit.
 *
 * Represents `purchase_units` in order request.
 * https://developer.paypal.com/docs/api/orders/v2/
 */
final class Unit implements DataTransferInterface
{
    /**
     * @param array<int,\WebServCo\Payment\Paypal\DataTransfer\Purchase\Item> $items
     */
    public function __construct(public readonly array $items, public readonly Amount $amount)
    {
    }
}
