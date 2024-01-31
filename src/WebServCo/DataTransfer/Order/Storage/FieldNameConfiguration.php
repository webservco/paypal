<?php

declare(strict_types=1);

namespace WebServCo\DataTransfer\Order\Storage;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final readonly class FieldNameConfiguration implements DataTransferInterface
{
    public function __construct(
        public string $orderReference = 'order_reference',
        public string $orderTotal = 'order_total',
        public ?string $orderCurrency = 'order_currency',
        public string $paymentStatus = 'payment_status',
        public string $paymentEventDateTime = 'payment_event_date_time',
    ) {
    }
}
