<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final class PaypalOptions implements DataTransferInterface
{
    public function __construct(
        public readonly string $apiBaseUrl,
        public readonly string $clientId,
        public readonly string $secret,
    ) {
    }
}
