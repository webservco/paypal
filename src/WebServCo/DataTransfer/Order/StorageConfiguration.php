<?php

declare(strict_types=1);

namespace WebServCo\DataTransfer\Order;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;
use WebServCo\DataTransfer\Order\Storage\FieldNameConfiguration;
use WebServCo\DataTransfer\Order\Storage\TableNameConfiguration;

final readonly class StorageConfiguration implements DataTransferInterface
{
    public function __construct(
        public string $defaultCurrency = 'EUR',
        public FieldNameConfiguration $fieldNameConfiguration = new FieldNameConfiguration(),
        public TableNameConfiguration $tableNameConfiguration = new TableNameConfiguration(),
    ) {
    }
}
