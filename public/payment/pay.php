<?php

/**
 * Pay page.
 */

declare(strict_types=1);

/**
 * PayPal includes path.
 *
 * Usually located in library project, or in current project if custom implementation is used.
 * Example for library project if current file is in `public/payment` in local project:
 * $paypalIncludesPath = realpath(
 * __DIR__ . '/../../vendor/webservco/paypal/src/example_implementation/',
 * ) . DIRECTORY_SEPARATOR;
 * Example for local project:
 * $paypalIncludesPath = realpath(__DIR__ ) . DIRECTORY_SEPARATOR;
 */
$paypalIncludesPath = realpath(__DIR__ . '/../../src/example_implementation');
if ($paypalIncludesPath === false) {
    throw new UnexpectedValueException('Failed to retrieve path.');
}
$paypalIncludesPath .= DIRECTORY_SEPARATOR;

// Current project path (where dependencies are installed)
$projectPath = realpath(__DIR__ . '/../..');
if ($projectPath === false) {
    throw new UnexpectedValueException('Failed to retrieve path.');
}
$projectPath .= DIRECTORY_SEPARATOR;

/**
 * Load dependencies
 *
 * @psalm-suppress UnresolvableInclude
 */
require sprintf('%svendor%sautoload.php', $projectPath, DIRECTORY_SEPARATOR);

/**
 * Load payment system include.
 *
 * @psalm-suppress UnresolvableInclude
 */
require sprintf('%spay.inc.php', $paypalIncludesPath);
