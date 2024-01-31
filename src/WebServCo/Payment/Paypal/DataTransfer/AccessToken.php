<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

final class AccessToken implements DataTransferInterface
{
    public function __construct(public readonly string $token, public readonly string $expireDateTime)
    {
    }
}
