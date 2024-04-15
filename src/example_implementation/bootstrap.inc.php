<?php

/**
 * Payment sys bootstrap file.
 */

declare(strict_types=1);

use Symfony\Component\HttpClient\Psr18Client;
use WebServCo\Configuration\Factory\ServerConfigurationGetterFactory;
use WebServCo\Configuration\Service\ConfigurationFileProcessor;
use WebServCo\Configuration\Service\IniServerConfigurationContainer;
use WebServCo\Data\Service\Extraction\ArrayStorageService;
use WebServCo\Data\Service\Extraction\Loose\LooseArrayNonEmptyDataExtractionService;
use WebServCo\Data\Service\Extraction\Loose\LooseDataExtractionService;
use WebServCo\Data\Service\Extraction\Loose\LooseNonEmptyDataExtractionService;
use WebServCo\Database\Factory\PDOContainerMySQLFactory;
use WebServCo\DataTransfer\Order\Storage\FieldNameConfiguration;
use WebServCo\DataTransfer\Order\Storage\TableNameConfiguration;
use WebServCo\DataTransfer\Order\StorageConfiguration;
use WebServCo\Log\Factory\ContextFileLoggerFactory;
use WebServCo\Payment\Paypal\DataTransfer\PaypalOptions;
use WebServCo\Payment\Paypal\Service\Authentication\AccessTokenService;
use WebServCo\Payment\Paypal\Service\Checkout\OrdersService;
use WebServCo\Storage\Order\OrderPaymentStorage;
use WebServCo\Storage\Payment\AccessTokenStorage;

/**
 * @phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
 */

/**
 * Included file validation.
 */
assert(isset($orderReference) && is_string($orderReference));
assert(isset($projectPath) && is_string($projectPath));

/**
 * Configuration.
 */
$configurationContainer = new IniServerConfigurationContainer();
$configurationFileProcessor = new ConfigurationFileProcessor(
    $configurationContainer->getConfigurationDataProcessor(),
    $configurationContainer->getConfigurationLoader(),
    $configurationContainer->getConfigurationSetter(),
);
$configurationFileProcessor->processConfigurationFile($projectPath, 'config', '.env.ini');

$configurationGetterFactory = new ServerConfigurationGetterFactory();
$configurationGetter = $configurationGetterFactory->createConfigurationGetter();

$appBaseUrlSettingKey = $configurationGetter->getString('PAYMENT_APP_BASE_URL_SETTING_KEY');
// Not working: @psalm-suppress UnusedVariable
$appBaseUrl = $configurationGetter->getString($appBaseUrlSettingKey);
if ($appBaseUrl === '') {
    throw new UnexpectedValueException('App base URL is empty');
}

/**
 * Logger.
 */
$loggerFactory = new ContextFileLoggerFactory(sprintf('%svar/log/', $projectPath));
$logger = $loggerFactory->createLogger('payment');

/**
 * Services
 */

$arrayNonEmptyDataExtractionService = new LooseArrayNonEmptyDataExtractionService(
    new ArrayStorageService(),
    new LooseDataExtractionService(),
    new LooseNonEmptyDataExtractionService(),
);
$pdoContainerFactory = new PDOContainerMySQLFactory(
    $configurationGetter->getString('DB_HOST'),
    $configurationGetter->getInt('DB_PORT'),
    $configurationGetter->getString('DB_NAME'),
    $configurationGetter->getString('DB_USER'),
    $configurationGetter->getString('DB_PASSWORD'),
);
$pdoContainer = $pdoContainerFactory->createPDOContainer();

$fieldNameOrderCurrency = $configurationGetter->getString('PAYMENT_FIELD_NAME_ORDER_CURRENCY');
$storageConfiguration = new StorageConfiguration(
    $configurationGetter->getString('PAYMENT_DEFAULT_CURRENCY'),
    new FieldNameConfiguration(
        $configurationGetter->getString('PAYMENT_FIELD_NAME_ORDER_REFERENCE'),
        $configurationGetter->getString('PAYMENT_FIELD_NAME_ORDER_TOTAL'),
        // Not using order level currency for current project.
        $fieldNameOrderCurrency !== '' ? $fieldNameOrderCurrency : null,
        $configurationGetter->getString('PAYMENT_FIELD_NAME_PAYMENT_STATUS'),
        $configurationGetter->getString('PAYMENT_FIELD_NAME_PAYMENT_TIME'),
    ),
    new TableNameConfiguration(
        $configurationGetter->getString('PAYMENT_TABLE_NAME_ORDERS'),
        $configurationGetter->getString('PAYMENT_TABLE_NAME_TOKEN'),
    ),
);

// Not working: @psalm-suppress UnusedVariable
$orderPaymentStorage = new OrderPaymentStorage(
    $arrayNonEmptyDataExtractionService,
    $pdoContainer,
    $storageConfiguration,
);

/**
 * HTTP.
 *
 * Implement PSR-18 and PSR-17 via:
 * - symfony/http-client
 * - nyholm/psr7
 *
 * https://symfony.com/doc/current/http_client.html#psr-18-and-psr-17
 */
$psrImplementation = new Psr18Client();

$paypalOptions = new PaypalOptions(
    $configurationGetter->getString('PAYPAL_API_BASE_URL'),
    $configurationGetter->getString('PAYPAL_API_CLIENT_ID'),
    $configurationGetter->getString('PAYPAL_API_SECRET'),
);

$accessTokenStorage = new AccessTokenStorage($arrayNonEmptyDataExtractionService, $pdoContainer, $storageConfiguration);
$accessTokenService = new AccessTokenService(
    $psrImplementation,
    $logger,
    $paypalOptions,
    $psrImplementation,
    $psrImplementation,
);
try {
    // Not working: @psalm-suppress UnusedVariable
    $accessToken = $accessTokenStorage->fetchCurrentAccessToken();
} catch (UnexpectedValueException) {
    $accessToken = $accessTokenService->getAccessToken();
    $accessTokenStorage->storeAccessToken($accessToken);
}

// Not working: @psalm-suppress UnusedVariable
$ordersService = new OrdersService($psrImplementation, $logger, $paypalOptions, $psrImplementation, $psrImplementation);
