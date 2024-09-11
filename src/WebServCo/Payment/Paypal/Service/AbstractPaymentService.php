<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\Service;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;
use WebServCo\Payment\Paypal\DataTransfer\AccessToken;
use WebServCo\Payment\Paypal\DataTransfer\PaypalOptions;

use function base64_encode;
use function is_array;
use function json_decode;
use function sprintf;
use function uniqid;

use const JSON_THROW_ON_ERROR;

abstract class AbstractPaymentService
{
    public function __construct(
        protected ClientInterface $httpClient,
        private LoggerInterface $logger,
        protected PaypalOptions $paypalOptions,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    protected function createAuthenticationRequest(string $clientId, string $secret, string $uri): RequestInterface
    {
        $request = $this->requestFactory->createRequest('POST', $uri)
        ->withHeader('Authorization', sprintf('Basic %s', base64_encode(sprintf('%s:%s', $clientId, $secret))))
        ->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        return $request->withBody($this->streamFactory->createStream('grant_type=client_credentials'));
    }

    protected function createApiRequest(
        AccessToken $accessToken,
        string $method,
        string $uri,
        ?string $body,
    ): RequestInterface {
        $request = $this->requestFactory->createRequest($method, $uri)
        ->withHeader('Authorization', sprintf('Bearer %s', $accessToken->token))
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('PayPal-Request-Id', uniqid())
        ->withHeader('Prefer', 'return=minimal');

        if ($body === null) {
            return $request;
        }

        return $request->withBody($this->streamFactory->createStream($body));
    }

    /**
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @return array<mixed>
     */
    protected function getResponseBodyAsArray(ResponseInterface $response): array
    {
        $response->getBody()->rewind();

        $body = $response->getBody()->getContents();

        // Important! Otherwise, the stream body contents can not be retrieved later.
        $response->getBody()->rewind();

        if ($body === '') {
            // Possible situation: the body contents were read elsewhere and the stream was not rewinded.
            throw new UnexpectedValueException('Response body is empty.');
        }

        $array = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($array)) {
            throw new UnexpectedValueException('Error decoding JSON data.');
        }

        return $array;
    }

    protected function logRequest(RequestInterface $request): true
    {
        $request->getBody()->rewind();

        $this->logger->debug(
            'Request debug (context).',
            [
                'request_body' => $request->getBody()->getContents(),
                'request_headers' => $request->getHeaders(),
                'request_method' => $request->getMethod(),
                'request_uri' => $request->getUri()->__toString(),
            ],
        );

        // Important! Otherwise, the stream body contents can not be retrieved later.
        $request->getBody()->rewind();

        return true;
    }

    protected function logResponse(ResponseInterface $response): true
    {
        $response->getBody()->rewind();

        $this->logger->debug(
            'Response debug (context).',
            [
                'response_body' => $response->getBody()->getContents(),
                'response_headers' => $response->getHeaders(),
                'response_reason_phrase' => $response->getReasonPhrase(),
                'response_status' => $response->getStatusCode(),
            ],
        );

        // Important! Otherwise, the stream body contents can not be retrieved later.
        $response->getBody()->rewind();

        return true;
    }
}
