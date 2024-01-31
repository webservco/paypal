<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer\Purchase;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * Breakdown.
 *
 * Represents `purchase_units.amount.breakdown` in order creation request.
 * https://developer.paypal.com/docs/api/orders/v2/
 * Not using camel case in order to comply with specification
 * (object is converted directly to JSON).
 *
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 */
final class Breakdown implements DataTransferInterface
{
    public function __construct(public readonly Amount $item_total)
    {
    }
}
