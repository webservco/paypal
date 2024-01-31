<?php

declare(strict_types=1);

use Psr\Log\NullLogger;
use WebServCo\Configuration\Contract\ConfigurationGetterInterface;
use WebServCo\Contract\Storage\Order\OrderPaymentStorageInterface;
use WebServCo\Payment\Paypal\DataTransfer\AccessToken;
use WebServCo\Payment\Paypal\DataTransfer\Application\Context;
use WebServCo\Payment\Paypal\DataTransfer\Purchase\Amount;
use WebServCo\Payment\Paypal\DataTransfer\Purchase\Item;
use WebServCo\Payment\Paypal\Service\Checkout\OrdersService;

// Included file validation.
assert(isset($paypalIncludesPath) && is_string($paypalIncludesPath));

$logger = new NullLogger();

// @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
/**
 * @psalm-suppress PossiblyInvalidCast
 */
$orderReference = array_key_exists('orderReference', $_GET)
? (string) $_GET['orderReference']
: null;
// @phpcs:enable

try {
    /**
     * Bootstrap
     *
     * @psalm-suppress UnresolvableInclude
     */
    require sprintf('%sbootstrap.inc.php', $paypalIncludesPath);
    assert(isset($accessToken) && $accessToken instanceof AccessToken);
    assert(isset($configurationGetter) && $configurationGetter instanceof ConfigurationGetterInterface);
    assert(isset($orderPaymentStorage) && $orderPaymentStorage instanceof OrderPaymentStorageInterface);
    assert(isset($ordersService) && $ordersService instanceof OrdersService);
    assert(isset($urlMain) && is_string($urlMain));

    if ($orderReference === null) {
        throw new UnexpectedValueException('Missing orderReference.');
    }

    /**
     * Functionality below.
     */

    $orderSummary = $orderPaymentStorage->fetchOrderSummary($orderReference);

    /**
     * Check if already paid.
     */
    $orderStatus = $orderPaymentStorage->fetchOrderStatus($orderReference);
    $ordersService->validateOrderStatusBeforeCreation($orderStatus);

    /**
     * Payment sys.
     */
    $ordersService->validateOrderCurrency($orderSummary->currency);

    $orderData = $ordersService->createOrder(
        $accessToken,
        new Item(
            sprintf('Order %s', $orderReference),
            '',
            1,
            new Amount($orderSummary->currency, $orderSummary->total),
        ),
        new Context(
            sprintf('%spayment/return.php?orderReference=%s', $urlMain, $orderReference),
            sprintf(
                '%s%s?orderReference=%s',
                $urlMain,
                $configurationGetter->getString('PAYMENT_CANCEL_LOCATION'),
                $orderReference,
            ),
        ),
    );
    $ordersService->validateOrderStatusAfterCreation($orderData->status);

    // Store payment data.
    $orderPaymentStorage->updateOrderData($orderReference, $orderData);

    // Redirect to payment page.
    header(
        sprintf(
            'Location: %s/checkoutnow?token=%s',
            $configurationGetter->getString('PAYPAL_WEB_BASE_URL'),
            $orderData->id,
        ),
        true,
        302,
    );
    exit;
} catch (Throwable $throwable) {
    $logger->error($throwable->getMessage(), ['throwable' => $throwable]);
    echo $throwable->getMessage();
    exit;
}
