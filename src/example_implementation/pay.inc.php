<?php

/**
 * @phan-file-suppress PhanRedundantConditionInGlobalScope
 */

declare(strict_types=1);

use Psr\Log\NullLogger;
use WebServCo\Payment\Paypal\DataTransfer\Application\Context;
use WebServCo\Payment\Paypal\DataTransfer\PaymentBootstrap;
use WebServCo\Payment\Paypal\DataTransfer\Purchase\Amount;
use WebServCo\Payment\Paypal\DataTransfer\Purchase\Item;

// Included file validation.
assert(isset($paypalIncludesPath) && is_string($paypalIncludesPath));

$logger = new NullLogger();

// @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
$orderReference = array_key_exists('orderReference', $_GET) && is_scalar($_GET['orderReference'])
? (string) $_GET['orderReference']
: null;
$languageCode = array_key_exists('languageCode', $_GET) && is_scalar($_GET['languageCode'])
    ? (string) $_GET['languageCode']
    : null;
// @phpcs:enable

try {
    /**
     * Bootstrap
     *
     * @psalm-suppress UnresolvableInclude
     */
    $bootstrap = require sprintf('%sbootstrap.inc.php', $paypalIncludesPath);
    assert($bootstrap instanceof PaymentBootstrap);

    if ($orderReference === null) {
        throw new UnexpectedValueException('Missing orderReference.');
    }

    /**
     * Functionality below.
     */

    $orderSummary = $bootstrap->orderPaymentStorage->fetchOrderSummary($orderReference);

    /**
     * Check if already paid.
     */
    $orderPaymentStatus = $bootstrap->orderPaymentStorage->fetchOrderPaymentStatus($orderReference);
    $bootstrap->ordersService->validateOrderPaymentStatusBeforeCreation($orderPaymentStatus);

    /**
     * Payment sys.
     */
    $bootstrap->ordersService->validateOrderCurrency($orderSummary->currency);

    $orderData = $bootstrap->ordersService->createOrder(
        $bootstrap->accessToken,
        new Item(
            sprintf('Order %s', $orderReference),
            '',
            1,
            new Amount($orderSummary->currency, $orderSummary->total),
        ),
        new Context(
            sprintf(
                '%spayment/return.php?orderReference=%s%s',
                $bootstrap->appBaseUrl,
                $orderReference,
                $languageCode !== null
                    ? sprintf('&languageCode=%s', $languageCode)
                    : '',
            ),
            sprintf(
                '%s%s?orderReference=%s%s',
                $bootstrap->appBaseUrl,
                $bootstrap->configurationGetter->getString('PAYMENT_CANCEL_LOCATION'),
                $orderReference,
                $languageCode !== null
                    ? sprintf('&languageCode=%s', $languageCode)
                    : '',
            ),
        ),
    );
    $bootstrap->ordersService->validatePaymentOrderStatusAfterCreation($orderData->status);

    // Store payment data.
    $bootstrap->orderPaymentStorage->updateOrderData($orderReference, $orderData);

    // Redirect to payment page.
    header(
        sprintf(
            'Location: %s/checkoutnow?token=%s',
            $bootstrap->configurationGetter->getString('PAYPAL_WEB_BASE_URL'),
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
