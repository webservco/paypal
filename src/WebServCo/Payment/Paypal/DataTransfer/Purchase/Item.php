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
 * @SuppressWarnings("PHPMD.CamelCaseParameterName")
 */
final readonly class Item implements DataTransferInterface
{
    public function __construct(
        public string $name,
        public string $description,
        public int $quantity,
        public Amount $unit_amount,
    ) {
    }
}
