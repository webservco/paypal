<?php

/**
 * @phan-file-suppress PhanRedundantConditionInGlobalScope
 */

declare(strict_types=1);

use Psr\Log\NullLogger;
use WebServCo\Payment\Paypal\DataTransfer\PaymentBootstrap;

// Included file validation.
assert(isset($paypalIncludesPath) && is_string($paypalIncludesPath));

$logger = new NullLogger();

// @phpcs:disable SlevomatCodingStandard.Variables.DisallowSuperGlobalVariable.DisallowedSuperGlobalVariable
// orderReference is our internal order id
$orderReference = array_key_exists('orderReference', $_GET) && is_scalar($_GET['orderReference'])
    ? (string) $_GET['orderReference']
    : null;
$languageCode = array_key_exists('languageCode', $_GET) && is_scalar($_GET['languageCode'])
    ? (string) $_GET['languageCode']
    : null;
$paypalOrderId = array_key_exists('token', $_GET) && is_scalar($_GET['token'])
? (string) $_GET['token']
: null;
// There is also `PayerID` but we are not using it.
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
    if ($paypalOrderId === null) {
        throw new UnexpectedValueException('Missing paypalOrderId.');
    }

    // Functionality below.

    /**
     * Check order status.
     */
    // Get status from local database.
    $orderPaymentStatus = $bootstrap->orderPaymentStorage->fetchOrderPaymentStatus($orderReference);
    // Use the same check done after creation, at this point we have no other info.
    $bootstrap->ordersService->validatePaymentOrderStatusAfterCreation($orderPaymentStatus);

    /**
     * Payment sys.
     */

    // Get order data from PayPal
    $orderData = $bootstrap->ordersService->getOrderData($bootstrap->accessToken, $paypalOrderId);
    $bootstrap->ordersService->validateOrderPaymentStatusBeforeCapture($orderData->status);

    // Capture payment
    $orderData = $bootstrap->ordersService->captureOrder($bootstrap->accessToken, $paypalOrderId);
    $bootstrap->ordersService->validateOrderPaymentStatusAfterCapture($orderData->status);

    // Store payment data.
    $bootstrap->orderPaymentStorage->updateOrderData($orderReference, $orderData);

    // Redirect to result page.
    header(
        sprintf(
            'Location: %s%s?orderReference=%s&accessToken=%s%s',
            $bootstrap->appBaseUrl,
            $bootstrap->configurationGetter->getString('PAYMENT_RESULT_LOCATION'),
            $orderReference,
            $bootstrap->accessToken->token,
            $languageCode !== null
                ? sprintf('&languageCode=%s', $languageCode)
                : '',
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
