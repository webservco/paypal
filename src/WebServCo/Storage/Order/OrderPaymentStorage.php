<?php

declare(strict_types=1);

namespace WebServCo\Storage\Order;

use PDOStatement;
use UnexpectedValueException;
use WebServCo\Contract\Storage\Order\OrderPaymentStorageInterface;
use WebServCo\DataTransfer\Order\Summary;
use WebServCo\Payment\Paypal\DataTransfer\OrderData;
use WebServCo\Storage\AbstractStorage;

use function sprintf;

final class OrderPaymentStorage extends AbstractStorage implements OrderPaymentStorageInterface
{
    public function fetchOrderSummary(string $orderReference): Summary
    {
        $stmt = $this->createOrderSummaryFetchStatement();
        $stmt->execute([$orderReference]);

        $row = $this->pdoContainer->getPDOService()->fetchAssoc($stmt);

        if ($row === []) {
            throw new UnexpectedValueException('No data found in storage.');
        }

        return new Summary(
            $this->arrayNonEmptyDataExtractionService->getNonEmptyFloat(
                $row,
                $this->storageConfiguration->fieldNameConfiguration->orderTotal,
            ),
            $this->storageConfiguration->fieldNameConfiguration->orderCurrency !== null
            ? $this->arrayNonEmptyDataExtractionService->getNonEmptyString(
                $row,
                // Does not affect the customizable table field name (handled in `createOrderSummaryFetchStatement`)
                'order_currency',
            )
            : $this->storageConfiguration->defaultCurrency,
        );
    }

    public function fetchOrderStatus(string $orderReference): ?string
    {
        $stmt = $this->pdoContainer->getPDOService()->prepareStatement(
            $this->pdoContainer->getPDO(),
            sprintf(
                "SELECT `%s` FROM `%s` WHERE `%s` = ? LIMIT 1",
                $this->storageConfiguration->fieldNameConfiguration->paymentStatus,
                $this->storageConfiguration->tableNameConfiguration->order,
                $this->storageConfiguration->fieldNameConfiguration->orderReference,
            ),
        );
        $stmt->execute([$orderReference]);

        $row = $this->pdoContainer->getPDOService()->fetchAssoc($stmt);

        if ($row === []) {
            throw new UnexpectedValueException('No data found in storage.');
        }

        return $this->arrayNonEmptyDataExtractionService->getNonEmptyNullableString(
            $row,
            $this->storageConfiguration->fieldNameConfiguration->paymentStatus,
        );
    }

    public function updateOrderData(string $orderReference, OrderData $orderData): bool
    {
        $stmt = $this->pdoContainer->getPDOService()->prepareStatement(
            $this->pdoContainer->getPDO(),
            sprintf(
                "UPDATE `%s` SET `%s` = ?, `%s` = NOW() WHERE `%s` = ? LIMIT 1",
                $this->storageConfiguration->tableNameConfiguration->order,
                $this->storageConfiguration->fieldNameConfiguration->paymentStatus,
                $this->storageConfiguration->fieldNameConfiguration->paymentEventDateTime,
                $this->storageConfiguration->fieldNameConfiguration->orderReference,
            ),
        );

        return $stmt->execute([$orderData->status, $orderReference]);
    }

    private function createOrderSummaryFetchStatement(): PDOStatement
    {
        return $this->pdoContainer->getPDOService()->prepareStatement(
            $this->pdoContainer->getPDO(),
            sprintf(
                "SELECT `%s`, UPPER(%s) AS order_currency FROM `%s` WHERE `%s` = ? LIMIT 1",
                $this->storageConfiguration->fieldNameConfiguration->orderTotal,
                $this->storageConfiguration->fieldNameConfiguration->orderCurrency
                ?? sprintf("'%s'", $this->storageConfiguration->defaultCurrency),
                $this->storageConfiguration->tableNameConfiguration->order,
                $this->storageConfiguration->fieldNameConfiguration->orderReference,
            ),
        );
    }
}
