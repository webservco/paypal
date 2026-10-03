<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer\Application;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * Represents `application_context` in order request.
 * https://developer.paypal.com/docs/api/orders/v2/
 * Not using camel case in order to comply with specification
 * (object is converted directly to JSON).
 *
 * @SuppressWarnings("PHPMD.CamelCaseParameterName")
 */
final readonly class Context implements DataTransferInterface
{
    public function __construct(public string $return_url, public string $cancel_url)
    {
    }
}
