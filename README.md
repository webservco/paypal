# webservco/paypal

A PayPal REST API implementation.

---

## Storage

### Structure

Both table names and field names can be customized.

#### Table `order_payment`

##### Fields to add to an existing table

- Mandatory: `order_reference`: implementation specific, eg. `VARCHAR(45) NOT NULL`,
- Mandatory: `order_total`: implementation specific, eg. `DECIMAL(10,2) NOT NULL` 
- Optional: `order_currency`: `CHAR(3) NOT NULL`, 
  - [PayPal currency codes](https://developer.paypal.com/reference/currency-codes/)
- Mandatory: `payment_status` `VARCHAR(45) DEFAULT NULL`,
- Mandatory: `payment_event_date_time` `DATETIME DEFAULT NULL`,

##### New table example

```sql
CREATE TABLE order_payment (
    order_reference VARCHAR(45) NOT NULL,
    order_total DECIMAL(10,2) NOT NULL,
    order_currency CHAR(3) NOT NULL,
    payment_status VARCHAR(45) DEFAULT NULL,
    payment_event_date_time DATETIME DEFAULT NULL,
    PRIMARY KEY(order_reference)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```

#### Table `payment_access_token`

```sql
CREATE TABLE payment_access_token (
    token VARCHAR(100) NOT NULL,
    expire_date_time DATETIME NOT NULL,
    added_date_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY(token)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
```
---

## Configuration

Use environment variables (check example configuration) (app url, default currency, table and fields names);

If not using order level currency, make sure to set the `PAYMENT_DEFAULT_CURRENCY` options, and leave blank `PAYMENT_FIELD_NAME_ORDER_CURRENCY`.

If using a very specialized setup and using the configuration is not enough, you can implement `\WebServCo\Contract\Storage\Order\OrderPaymentStorageInterface` specifically for your project.

---

## Usage

An example is located in the directory `public/payment`.

Copy the files in your local project and adapt the paths.

Note: If a custom implementation is required, check and adapt the code located in `src/example_implementation`.

---

## Testing the example implementation

- Create `config/.env.ini`;
- Fill paypal info
- Start project: `ddev start`
- Create tables;
- Create a test order:
```sql
INSERT INTO `order_payment` (order_reference, order_total, order_currency) VALUES ('Test1', 123.45, 'EUR');
```
- Open payment page: `https://paypal.ddev.site/payment/pay.php?orderReference=Test1&languageCode=en`

Note: `languageCode` is optional and only used internally in implementing project.
If you set it when initialize the payment, it will arrive as parameter to the `PAYMENT_RESULT_LOCATION` and the `PAYMENT_CANCEL_LOCATION`.

---

## TODO

### [ ] Find a way to suppress Psalm "UnusedVariable" errors.

Not working (generates "UnusedPsalmSuppress" error):

```php
/**
 * @psalm-suppress UnusedVariable
 */
```

```php
/** @psalm-suppress UnusedVariable */
```

Reference: [Docblock suppression](https://psalm.dev/docs/running_psalm/dealing_with_code_issues/#docblock-suppression)

---