<?php

declare(strict_types=1);

namespace Zolta\Cqrs\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use Zolta\Cqrs\Contracts\TransactionManagerInterface;
use Zolta\Cqrs\Laravel\Database\LaravelTransactionManager;

class ZoltaCqrsServiceProvider extends ServiceProvider
{
    /**
     * @var list<string>
     */
    private const LEGACY_CQRS_KEYS = [
        'commands',
        'queries',
        'infrastructure_events',
        'cache',
        'cache_manifest',
        'map_cache',
        'map_keys',
        'options',
    ];

    /**
     * Register all CQRS service providers in order.
     */
    public function register(): void
    {
        $configPath = __DIR__.'/../config/zolta.php';
        $this->mergeConfigFrom($configPath, 'zolta');

        $defaults = (array) (require $configPath);
        $zoltaConfigured = (array) config('zolta', []);
        $talredConfigured = $this->app['config']->has('talred')
            ? (array) config('talred', [])
            : [];
        $configured = $this->mergeConfigRecursively($zoltaConfigured, $talredConfigured);

        $zoltaLegacy = [];
        foreach (self::LEGACY_CQRS_KEYS as $key) {
            if (array_key_exists($key, $zoltaConfigured)) {
                $zoltaLegacy[$key] = $zoltaConfigured[$key];
            }
        }

        $configured['cqrs'] = $this->mergeConfigRecursively(
            (array) ($defaults['cqrs'] ?? []),
            (array) ($zoltaConfigured['cqrs'] ?? []),
        );
        $configured['cqrs'] = $this->mergeConfigRecursively(
            $configured['cqrs'],
            $zoltaLegacy,
        );
        $configured['cqrs'] = $this->mergeConfigRecursively(
            $configured['cqrs'],
            (array) ($talredConfigured['cqrs'] ?? []),
        );

        $talredLegacy = [];
        foreach (self::LEGACY_CQRS_KEYS as $key) {
            if (array_key_exists($key, $talredConfigured)) {
                $talredLegacy[$key] = $talredConfigured[$key];
            }
        }
        $configured['cqrs'] = $this->mergeConfigRecursively(
            $configured['cqrs'],
            $talredLegacy,
        );

        foreach (self::LEGACY_CQRS_KEYS as $key) {
            $configured[$key] = $configured['cqrs'][$key] ?? ($configured[$key] ?? null);
        }

        // Talred is the public configuration surface; zolta remains a
        // mirrored technical compatibility surface for existing consumers.
        $this->app['config']->set('zolta', $configured);
        $this->app['config']->set('talred', $configured);

        // Transaction support
        $this->app->bind(TransactionManagerInterface::class, LaravelTransactionManager::class);

        // Core bindings
        $this->app->register(LaravelBridgeServiceProvider::class);
        $this->app->register(HydrationServiceProvider::class);

        // CQRS buses
        $this->app->register(CqrsServiceProvider::class);
        $this->app->register(QueryBusServiceProvider::class);
        $this->app->register(CommandBusServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // Map scanning (commands, queries, events)
        $this->app->register(CommandMapServiceProvider::class);
        $this->app->register(QueryMapServiceProvider::class);
        $this->app->register(EventMapServiceProvider::class);

        // Query options & artisan commands
        $this->app->register(QueryOptionsServiceProvider::class);
        $this->app->register(ZoltaCommandServiceProvider::class);
    }

    /**
     * Boot: publish configuration.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/zolta.php' => config_path('zolta.php'),
        ], 'zolta-cqrs-config');

        $this->publishes([
            __DIR__.'/../config/zolta.php' => config_path('talred.php'),
        ], 'talred-cqrs-config');
    }

    /**
     * @param  array<string,mixed>  $defaults
     * @param  array<string,mixed>  $configured
     * @return array<string,mixed>
     */
    private function mergeConfigRecursively(array $defaults, array $configured): array
    {
        $merged = $defaults;

        foreach ($configured as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeConfigRecursively($merged[$key], $value);

                continue;
            }

            $merged[$key] = $value;
        }

        return $merged;
    }
}
