<?php

declare(strict_types=1);

namespace WebServCo\Storage;

use WebServCo\Data\Contract\Extraction\Loose\LooseArrayNonEmptyDataExtractionServiceInterface;
use WebServCo\Database\Contract\PDOContainerInterface;
use WebServCo\DataTransfer\Order\StorageConfiguration;

abstract class AbstractStorage
{
    public function __construct(
        protected LooseArrayNonEmptyDataExtractionServiceInterface $arrayNonEmptyDataExtractionService,
        protected PDOContainerInterface $pdoContainer,
        protected StorageConfiguration $storageConfiguration,
    ) {
    }
}
