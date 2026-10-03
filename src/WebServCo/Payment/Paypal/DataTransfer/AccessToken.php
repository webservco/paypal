<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final readonly class AccessToken implements DataTransferInterface
{
    public function __construct(public string $token, public string $expireDateTime)
    {
    }
}
