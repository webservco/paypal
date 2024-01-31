<?php

declare(strict_types=1);

namespace WebServCo\Storage\Payment;

use UnexpectedValueException;
use WebServCo\Payment\Paypal\DataTransfer\AccessToken;
use WebServCo\Storage\AbstractStorage;

use function sprintf;

final class AccessTokenStorage extends AbstractStorage
{
    public function fetchCurrentAccessToken(): AccessToken
    {
        $stmt = $this->pdoContainer->getPDOService()->prepareStatement(
            $this->pdoContainer->getPDO(),
            sprintf(
                "SELECT token, expire_date_time FROM `%s`
                WHERE TIMESTAMPDIFF(MINUTE, NOW(), expire_date_time) >= 10",
                $this->storageConfiguration->tableNameConfiguration->paymentAccessToken,
            ),
        );
        $stmt->execute();

        $row = $this->pdoContainer->getPDOService()->fetchAssoc($stmt);

        if ($row === []) {
            throw new UnexpectedValueException('No data found in storage.');
        }

        return new AccessToken(
            $this->arrayNonEmptyDataExtractionService->getNonEmptyString($row, 'token'),
            $this->arrayNonEmptyDataExtractionService->getNonEmptyString($row, 'expire_date_time'),
        );
    }

    public function storeAccessToken(AccessToken $accessToken): bool
    {
        $stmt = $this->pdoContainer->getPDOService()->prepareStatement(
            $this->pdoContainer->getPDO(),
            sprintf(
                "INSERT INTO `%s` (token, expire_date_time) VALUES (?, ?)",
                $this->storageConfiguration->tableNameConfiguration->paymentAccessToken,
            ),
        );

        return $stmt->execute([$accessToken->token, $accessToken->expireDateTime]);
    }
}
