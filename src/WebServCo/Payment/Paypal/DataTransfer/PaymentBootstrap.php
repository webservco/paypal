<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer;

use WebServCo\Configuration\Contract\ConfigurationGetterInterface;
use WebServCo\Contract\Storage\Order\OrderPaymentStorageInterface;
use WebServCo\Data\Contract\Transfer\DataTransferInterface;
use WebServCo\Payment\Paypal\Service\Checkout\OrdersService;

final readonly class PaymentBootstrap implements DataTransferInterface
{
    public function __construct(
        public AccessToken $accessToken,
        public string $appBaseUrl,
        public ConfigurationGetterInterface $configurationGetter,
        public OrderPaymentStorageInterface $orderPaymentStorage,
        public OrdersService $ordersService,
    ) {
    }
}
