<?php

declare(strict_types=1);

/**
 * Expose the Talred public CQRS namespace without duplicating the Zolta
 * implementation. Existing applications continue to use Zolta namespaces;
 * new applications may import Talred namespaces during the compatibility
 * period.
 */
if (! defined('TALRED_CQRS_COMPATIBILITY_LOADER_REGISTERED')) {
    define('TALRED_CQRS_COMPATIBILITY_LOADER_REGISTERED', true);

    spl_autoload_register(static function (string $class): void {
        $prefixes = [
            'Talred\\Cqrs\\Laravel\\' => 'Zolta\\Cqrs\\Laravel\\',
            'Talred\\Cqrs\\Support\\' => 'Zolta\\Cqrs\\Support\\',
            'Talred\\Cqrs\\' => 'Zolta\\Cqrs\\',
        ];

        foreach ($prefixes as $prefix => $legacyPrefix) {
            if (! str_starts_with($class, $prefix)) {
                continue;
            }

            if (
                class_exists($class, false)
                || interface_exists($class, false)
                || trait_exists($class, false)
                || (function_exists('enum_exists') && enum_exists($class, false))
            ) {
                return;
            }

            $legacy = $legacyPrefix.substr($class, strlen($prefix));
            $resolved = class_exists($legacy)
                || interface_exists($legacy)
                || trait_exists($legacy)
                || (function_exists('enum_exists') && enum_exists($legacy));

            if (! $resolved) {
                return;
            }

            class_alias($legacy, $class);

            return;
        }
    }, true, false);
}
