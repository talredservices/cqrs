<?php

declare(strict_types=1);

namespace ZoltaTests\Integration;

use Orchestra\Testbench\TestCase;
use Zolta\Cqrs\Laravel\Providers\ZoltaCqrsServiceProvider;

final class TalredCqrsConfigurationProviderTest extends TestCase
{
    public function test_talred_configuration_is_resolved_and_mirrored_to_zolta_consumers(): void
    {
        $this->app['config']->set('zolta', [
            'queries' => [['path' => '/legacy/queries', 'namespace' => 'App\\Legacy\\']],
        ]);
        $this->app['config']->set('talred', [
            'cqrs' => [
                'queries' => [['path' => '/talred/queries', 'namespace' => 'App\\Talred\\']],
                'map_keys' => ['query' => 'talred.query.map'],
            ],
        ]);
        $this->app->register(ZoltaCqrsServiceProvider::class);

        $this->assertSame([
            ['path' => '/talred/queries', 'namespace' => 'App\\Talred\\'],
        ], config('talred.cqrs.queries'));
        $this->assertSame(config('talred.cqrs.queries'), config('zolta.cqrs.queries'));
        $this->assertSame('talred.query.map', config('talred.cqrs.map_keys.query'));
        $this->assertSame(config('talred.cqrs.map_keys.query'), config('zolta.map_keys.query'));
    }
}
