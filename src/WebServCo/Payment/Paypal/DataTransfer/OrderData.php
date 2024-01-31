<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * Order data.
 *
 * Represents the minimal response body received when creating an order
 * when using the "return=minimal" "Prefer" header.
 * https://developer.paypal.com/docs/api/orders/v2/
 */
final class OrderData implements DataTransferInterface
{
    public function __construct(public readonly string $id, public readonly string $status)
    {
    }
}
