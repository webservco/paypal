<?php

declare(strict_types=1);

namespace WebServCo\Storage\Order;

use Override;
use PDOStatement;
use UnexpectedValueException;
use WebServCo\Contract\Storage\Order\OrderPaymentStorageInterface;
use WebServCo\DataTransfer\Order\Summary;
use WebServCo\Payment\Paypal\DataTransfer\OrderData;
use WebServCo\Storage\AbstractStorage;

use function sprintf;

final class OrderPaymentStorage extends AbstractStorage implements OrderPaymentStorageInterface
{
    #[Override]
    public function fetchOrderSummary(string $orderReference): Summary
    {
        $stmt = $this->createOrderSummaryFetchStatement();
        $stmt->execute([$orderReference]);

        $row = $this->pdoContainer->getPDOService()->fetchAssoc($stmt);

        return $this->hydrateOrderSummary($row);
    }

    #[Override]
    public function fetchOrderPaymentStatus(string $orderReference): ?string
    {
        $stmt = $this->pdoContainer->getPDOService()->prepareStatement(
            $this->pdoContainer->getPDO(),
            sprintf(
                "SELECT `%s` FROM `%s` WHERE `%s` = ? LIMIT 1",
                $this->storageConfiguration->fieldNameConfiguration->orderPaymentStatus,
                $this->storageConfiguration->tableNameConfiguration->order,
                $this->storageConfiguration->fieldNameConfiguration->orderReference,
            ),
        );
        $stmt->execute([$orderReference]);

        $row = $this->pdoContainer->getPDOService()->fetchAssoc($stmt);

        return $this->hydrateOrderPaymentStatus($row);
    }

    #[Override]
    public function updateOrderData(string $orderReference, OrderData $orderData): bool
    {
        $stmt = $this->pdoContainer->getPDOService()->prepareStatement(
            $this->pdoContainer->getPDO(),
            sprintf(
                "UPDATE `%s` SET `%s` = ?, `%s` = NOW() WHERE `%s` = ? LIMIT 1",
                $this->storageConfiguration->tableNameConfiguration->order,
                $this->storageConfiguration->fieldNameConfiguration->orderPaymentStatus,
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

    /**
     * @param array<string,scalar|null> $data
     */
    private function hydrateOrderCurrency(array $data): string
    {
        return $this->storageConfiguration->fieldNameConfiguration->orderCurrency !== null
            ? $this->arrayNonEmptyDataExtractionService->getNonEmptyString(
                $data,
                /**
                 * Does not affect the customizable table field name
                 * (handled in `createOrderSummaryFetchStatement`)
                 */
                'order_currency',
            )
            : $this->storageConfiguration->defaultCurrency;
    }

    /**
     * @param array<string,scalar|null> $data
     */
    private function hydrateOrderPaymentStatus(array $data): ?string
    {
        if ($data === []) {
            throw new UnexpectedValueException('No data found in storage.');
        }

        try {
            return $this->arrayNonEmptyDataExtractionService->getNonEmptyNullableString(
                $data,
                $this->storageConfiguration->fieldNameConfiguration->orderPaymentStatus,
            );
        } catch (UnexpectedValueException $e) {
            throw new UnexpectedValueException(
                sprintf('Error fetching order payment status: "%s".', $e->getMessage()),
                $e->getCode(),
                $e,
            );
        }
    }

    /**
     * @param array<string,scalar|null> $data
     */
    private function hydrateOrderSummary(array $data): Summary
    {
        if ($data === []) {
            throw new UnexpectedValueException('No data found in storage.');
        }

        try {
            return new Summary(
                $this->arrayNonEmptyDataExtractionService->getNonEmptyFloat(
                    $data,
                    $this->storageConfiguration->fieldNameConfiguration->orderTotal,
                ),
                $this->hydrateOrderCurrency($data),
            );
        } catch (UnexpectedValueException $e) {
            throw new UnexpectedValueException(
                sprintf('Error fetching order summary data: "%s".', $e->getMessage()),
                $e->getCode(),
                $e,
            );
        }
    }
}
