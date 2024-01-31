<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\Service\Checkout;

use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;
use WebServCo\Payment\Paypal\DataTransfer\AccessToken;
use WebServCo\Payment\Paypal\DataTransfer\Application\Context;
use WebServCo\Payment\Paypal\DataTransfer\OrderData;
use WebServCo\Payment\Paypal\DataTransfer\OrderRequest;
use WebServCo\Payment\Paypal\DataTransfer\Purchase\Amount;
use WebServCo\Payment\Paypal\DataTransfer\Purchase\Breakdown;
use WebServCo\Payment\Paypal\DataTransfer\Purchase\Item;
use WebServCo\Payment\Paypal\DataTransfer\Purchase\Unit;
use WebServCo\Payment\Paypal\Service\AbstractPaymentService;

use function array_key_exists;
use function in_array;
use function json_encode;
use function sprintf;
use function strtoupper;

final class OrdersService extends AbstractPaymentService
{
    private const API_PATH = '/v2/checkout/orders';

    // https://developer.paypal.com/reference/currency-codes/
    private const SUPPORTED_CURRENCIES = [
        'AUD',
        'BRL',
        'CAD',
        'CNY',
        'CZK',
        'DKK',
        'EUR',
        'HKD',
        'HUF',
        'ILS',
        'JPY',
        'MYR',
        'MXN',
        'TWD',
        'NZD',
        'NOK',
        'PHP',
        'PLN',
        'GBP',
        'SGD',
        'SEK',
        'CHF',
        'THB',
        'USD',
    ];

    /**
     * Capture order.
     *
     * https://developer.paypal.com/docs/api/orders/v2/#orders_capture
     */
    public function captureOrder(AccessToken $accessToken, string $orderReference): OrderData
    {
        $request = $this->createApiRequest(
            $accessToken,
            'POST',
            sprintf('%s%s/%s/capture', $this->paypalOptions->apiBaseUrl, self::API_PATH, $orderReference),
            null,
        );
        $this->logRequest($request);

        $response = $this->httpClient->sendRequest($request);
        $this->logResponse($response);

        $statusCode = $response->getStatusCode();

        // Response code must be 201 Created
        // https://developer.paypal.com/api/rest/reference/orders/v2/errors/#create-order
        if ($statusCode !== 201) {
            throw new UnexpectedValueException('Response status code is different than expected.');
        }

        return $this->createOrderDataFromResponse($response);
    }

    /**
     * Create order.
     *
     * https://developer.paypal.com/docs/api/orders/v2/
     */
    public function createOrder(AccessToken $accessToken, Item $item, Context $context): OrderData
    {
        $body = json_encode($this->createOrderRequest($item, $context));
        if ($body === false) {
            throw new UnexpectedValueException('Error encoding data to JSON.');
        }

        $request = $this->createApiRequest(
            $accessToken,
            'POST',
            sprintf('%s%s', $this->paypalOptions->apiBaseUrl, self::API_PATH),
            $body,
        );
        $this->logRequest($request);

        $response = $this->httpClient->sendRequest($request);
        $this->logResponse($response);

        $statusCode = $response->getStatusCode();

        // Response code must be 201 Created
        // https://developer.paypal.com/api/rest/reference/orders/v2/errors/#create-order
        if ($statusCode !== 201) {
            throw new UnexpectedValueException('Response status code is different than expected.');
        }

        return $this->createOrderDataFromResponse($response);
    }

    /**
     * Get order details from paypal.
     *
     * Only returns minimal order data required to complete the transaction
     */
    public function getOrderData(AccessToken $accessToken, string $orderReference): OrderData
    {
        $request = $this->createApiRequest(
            $accessToken,
            'GET',
            sprintf('%s%s/%s', $this->paypalOptions->apiBaseUrl, self::API_PATH, $orderReference),
            null,
        );
        $this->logRequest($request);

        $response = $this->httpClient->sendRequest($request);
        $this->logResponse($response);

        $statusCode = $response->getStatusCode();

        // https://developer.paypal.com/api/rest/reference/orders/v2/errors/#create-order
        if ($statusCode !== 200) {
            throw new UnexpectedValueException('Response status code is different than expected.');
        }

        return $this->createOrderDataFromResponse($response);
    }

    public function validateOrderCurrency(string $currency): bool
    {
        $currency = strtoupper($currency);

        if (in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            return true;
        }

        throw new UnexpectedValueException('Unsupported order currency.');
    }

    public function validateOrderStatusAfterCapture(?string $orderStatus): bool
    {
        if ($orderStatus === 'COMPLETED') {
            return true;
        }

        throw new UnexpectedValueException('Invalid order status.');
    }

    public function validateOrderStatusAfterCreation(?string $orderStatus): bool
    {
        if ($orderStatus === 'CREATED') {
            return true;
        }

        throw new UnexpectedValueException('Invalid order status.');
    }

    public function validateOrderStatusBeforeCapture(?string $orderStatus): bool
    {
        if (in_array($orderStatus, ['APPROVED', null], true)) {
            return true;
        }

        throw new UnexpectedValueException('Invalid order status.');
    }

    public function validateOrderStatusBeforeCreation(?string $orderStatus): bool
    {
        if (in_array($orderStatus, ['CREATED', null], true)) {
            return true;
        }

        throw new UnexpectedValueException('Invalid order status.');
    }

    private function createOrderDataFromResponse(ResponseInterface $response): OrderData
    {
        $array = $this->getResponseBodyAsArray($response);

        $id = array_key_exists('id', $array)
            ? (string) $array['id']
            : '';
        $status = array_key_exists('status', $array)
            ? (string) $array['status']
            : '';

        if ($id === '' || $status === '') {
            throw new UnexpectedValueException('Empty required fields.');
        }

        return new OrderData($id, $status);
    }

    private function createOrderRequest(Item $item, Context $context): OrderRequest
    {
        // Total for all items, and extra costs.
        $amount = new Amount($item->unit_amount->currency_code, $item->quantity * $item->unit_amount->value);

        return new OrderRequest(
            'CAPTURE',
            [
                new Unit(
                    [$item],
                    new Amount(
                        $amount->currency_code,
                        $amount->value,
                        new Breakdown(
                            // breakdown.item_total is the same as amount (total) because there are no other costs.
                            $amount,
                        ),
                    ),
                ),
            ],
            $context,
        );
    }
}
