# webservco/paypal

A PHP component/library. Please see [webservco/sandbox](https://webservco.github.io/sandbox/) for an example project using these types of components.

Takes payment for an order through the PayPal REST API ([Orders v2](https://developer.paypal.com/docs/api/orders/v2/)): create the order, send the customer to PayPal, then capture the payment.

- Requires: PayPal REST API credentials, two database tables (below), `symfony/http-client` and `nyholm/psr7` (installed with this package)
- Related: `webservco/configuration`, `webservco/database`, `webservco/log`

---

## Installation

```shell
composer require webservco/paypal
```

---

## Usage

A working example is in `public/payment/` (entry points) and `src/example_implementation/` (logic). Copy both into your project and adjust the paths.

The flow:

1. `pay.php?orderReference=Test1` loads the order, creates a PayPal order and redirects the customer to PayPal.
2. PayPal sends the customer back to `return.php`, which captures the payment, saves the status and redirects to `PAYMENT_RESULT_LOCATION`.
3. If the customer cancels, PayPal sends them to `PAYMENT_CANCEL_LOCATION`.

`languageCode` is optional. If you pass it to `pay.php`, it's added to both the result and cancel URLs.

---

## Contracts

| Interface | Implementation | Purpose |
| --- | --- | --- |
| `Contract\Storage\Order\OrderPaymentStorageInterface` | `Storage\Order\OrderPaymentStorage` | Reads the order total and status, and saves the payment result. |
| none | `Storage\Payment\AccessTokenStorage` | Keeps the PayPal access token until it expires. |
| none | `Payment\Paypal\Service\Authentication\AccessTokenService` | Gets an access token from PayPal. |
| none | `Payment\Paypal\Service\Checkout\OrdersService` | Creates, reads and captures orders, and validates their status. |

Namespaces are relative to `WebServCo\`. If configuration isn't enough for your database, implement `OrderPaymentStorageInterface` yourself.

---

## Configuration

Copy `config/.env.development.ini` to `config/.env.ini` and fill in the PayPal credentials. Table and field names can be changed to match an existing table.

```ini
[Payment system]

; The configuration key that holds the application URL.
PAYMENT_APP_BASE_URL_SETTING_KEY = APP_URL
; Used when orders have no currency field.
PAYMENT_DEFAULT_CURRENCY = EUR

PAYMENT_FIELD_NAME_ORDER_REFERENCE = order_reference
PAYMENT_FIELD_NAME_ORDER_TOTAL = order_total
; Leave empty if orders have no currency field.
PAYMENT_FIELD_NAME_ORDER_CURRENCY = order_currency
PAYMENT_FIELD_NAME_ORDER_PAYMENT_STATUS = order_payment_status
PAYMENT_FIELD_NAME_PAYMENT_TIME = payment_event_date_time

PAYMENT_TABLE_NAME_ORDERS = payment_order
PAYMENT_TABLE_NAME_TOKEN = payment_access_token

PAYMENT_CANCEL_LOCATION = payment-ko.html
PAYMENT_RESULT_LOCATION = payment-finished.php

[PayPal]

PAYPAL_API_CLIENT_ID =
PAYPAL_API_SECRET =
; No trailing slash. Production: https://api-m.paypal.com and https://www.paypal.com
PAYPAL_API_BASE_URL = "https://api-m.sandbox.paypal.com"
PAYPAL_WEB_BASE_URL = "https://www.sandbox.paypal.com"
```

The example also reads `APP_URL` and the `DB_*` keys (see `webservco/database`).

### Tables

Add these fields to your orders table, or create a new one:

```sql
CREATE TABLE payment_order (
    order_reference VARCHAR(45) NOT NULL,
    order_total DECIMAL(10,2) NOT NULL,
    -- Optional. PayPal currency codes: https://developer.paypal.com/reference/currency-codes/
    order_currency CHAR(3) NOT NULL,
    order_payment_status VARCHAR(45) DEFAULT NULL,
    payment_event_date_time DATETIME DEFAULT NULL,
    PRIMARY KEY(order_reference)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE payment_access_token (
    token VARCHAR(100) NOT NULL,
    expire_date_time DATETIME NOT NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(token)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

---

## Notes

### Before copying the example

`return.inc.php` adds the PayPal access token to the result page URL (`&accessToken=...`). That puts the token in browser history and server logs. Remove it unless the result page needs it.

Both example scripts print the exception message on error. Show a generic message in production and keep the details in the log (`var/log/`, channel `payment`).

### Trying the example

1. Create `config/.env.ini` with sandbox credentials, then run `ddev start` and create the tables.
2. Add a test order: `INSERT INTO payment_order (order_reference, order_total, order_currency) VALUES ('Test1', 123.45, 'EUR');`
3. Open `https://paypal.ddev.site/payment/pay.php?orderReference=Test1&languageCode=en` and pay with a [sandbox test card](https://developer.paypal.com/tools/sandbox/card-testing/).

See also [Get started with PayPal REST APIs](https://developer.paypal.com/api/rest/).

---

## Development

```shell
# Start project.
ddev start
# Install dependencies.
ddev c u
# Run all validations.
ddev c c
# Run unit tests.
ddev c test:dox
```

---

## License

[MIT](LICENSE.md)
