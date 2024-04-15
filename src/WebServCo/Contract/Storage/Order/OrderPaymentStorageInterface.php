<?php

declare(strict_types=1);

namespace WebServCo\Contract\Storage\Order;

use WebServCo\DataTransfer\Order\Summary;
use WebServCo\Payment\Paypal\DataTransfer\OrderData;

interface OrderPaymentStorageInterface
{
    public function fetchOrderSummary(string $orderReference): Summary;

    public function fetchOrderPaymentStatus(string $orderReference): ?string;

    public function updateOrderData(string $orderReference, OrderData $orderData): bool;
}
