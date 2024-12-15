<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\Service\Authentication;

use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;
use WebServCo\Payment\Paypal\DataTransfer\AccessToken;
use WebServCo\Payment\Paypal\Service\AbstractPaymentService;

use function date;
use function sprintf;
use function time;

final class AccessTokenService extends AbstractPaymentService
{
    // Not a typo, must use v1, v2 gives 404
    private const API_PATH = '/v1/oauth2/token';

    public function getAccessToken(): AccessToken
    {
        $request = $this->createAuthenticationRequest(
            $this->paypalOptions->clientId,
            $this->paypalOptions->secret,
            sprintf('%s%s', $this->paypalOptions->apiBaseUrl, self::API_PATH),
        );
        $this->logRequest($request);

        $response = $this->httpClient->sendRequest($request);
        $this->logResponse($response);

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new UnexpectedValueException('Response status code is different than expected.');
        }

        return $this->createAccessTokenFromResponse($response);
    }

    private function createAccessTokenFromResponse(ResponseInterface $response): AccessToken
    {
        $array = $this->getResponseBodyAsArray($response);

        $token = $this->getStringFromResponseBodyArray($array, 'access_token');
        $expiresIn = $this->getIntFromResponseBodyArray($array, 'expires_in');

        if ($token === '' || $expiresIn === 0) {
            throw new UnexpectedValueException('Empty required fields.');
        }

        return new AccessToken(
            $token,
            date('Y-m-d H:i:s', time() + $expiresIn),
        );
    }
}
