<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer\Purchase;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * Item.
 *
 * Represents `purchase_units.items.{item}` in order request.
 * https://developer.paypal.com/docs/api/orders/v2/
 * Not using camel case in order to comply with specification
 * (object is converted directly to JSON).
 *
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 */
final class Item implements DataTransferInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $quantity,
        public readonly Amount $unit_amount,
    ) {
    }
}
