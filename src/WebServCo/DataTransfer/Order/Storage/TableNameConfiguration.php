<?php

declare(strict_types=1);

namespace WebServCo\DataTransfer\Order\Storage;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final readonly class TableNameConfiguration implements DataTransferInterface
{
    public function __construct(
        public string $order = 'payment_order',
        public string $paymentAccessToken = 'payment_access_token',
    ) {
    }
}
