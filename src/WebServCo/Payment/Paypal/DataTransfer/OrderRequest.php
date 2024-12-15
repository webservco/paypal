<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;
use WebServCo\Payment\Paypal\DataTransfer\Application\Context;

/**
 * Order request.
 *
 * Represents the minimal request body used when creating an order.
 * https://developer.paypal.com/docs/api/orders/v2/
 * Not using camel case in order to comply with specification
 * (object is converted directly to JSON).
 *
 * @SuppressWarnings("PHPMD.CamelCaseParameterName")
 */
final class OrderRequest implements DataTransferInterface
{
    /**
     * @param array<int,\WebServCo\Payment\Paypal\DataTransfer\Purchase\Unit> $purchase_units
     */
    public function __construct(
        public readonly string $intent,
        public readonly array $purchase_units,
        public readonly Context $application_context,
    ) {
    }
}
