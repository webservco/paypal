<?php

declare(strict_types=1);

namespace WebServCo\Payment\Paypal\DataTransfer\Purchase;

use JsonSerializable;
use Override;
use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * Amount.
 *
 * Represents
 * - `purchase_units.items.{item}.unit_amount`
 * - `purchase_units.amount`
 * in order creation request.
 * https://developer.paypal.com/docs/api/orders/v2/
 * Not using camel case in order to comply with specification
 * (object is converted directly to JSON).
 *
 * @SuppressWarnings("PHPMD.CamelCaseParameterName")
 */
final class Amount implements DataTransferInterface, JsonSerializable
{
    public function __construct(
        public readonly string $currency_code,
        public readonly float $value,
        public readonly ?Breakdown $breakdown = null,
    ) {
    }

    /**
     * Custom serialization to avoid problems with rounding.
     * Eg. 422 Unprocessable Entity
     * '{"name":"UNPROCESSABLE_ENTITY","details":[{
     * "field":"/purchase_units/@reference_id==\'default\'/amount/value",
     * "value":"79.989999999999995",
     * "issue":"DECIMAL_PRECISION",
     * "description":"If the currency supports decimals, only two decimal place precision is supported."}],
     * "message":"The requested action could not be performed, semantically incorrect,
     * or failed business validation.","debug_id":"6d56f7aed4e4",
     * "links":[{"href":"https://developer.paypal.com/docs/api/orders/v2/#error-DECIMAL_PRECISION",
     * "rel":"information_link","method":"GET"}]}'
     */
    #[Override]
    public function jsonSerialize(): mixed
    {
        return [
            'breakdown' => $this->breakdown,
            'currency_code' => $this->currency_code,
            'value' => (string) $this->value,
        ];
    }
}
