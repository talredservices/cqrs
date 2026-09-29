<?php

declare(strict_types=1);

namespace Zolta\Tests\Unit\Compatibility;

use PHPUnit\Framework\TestCase;
use Talred\Cqrs\Attributes\HandlesCommand;
use Talred\Cqrs\Commands\Command;
use Talred\Cqrs\Commands\Contracts\CommandInterface;
use Talred\Cqrs\Events\Traits\HandlesDomainEvents;
use Talred\Cqrs\Services\Result;

final class TalredNamespaceCompatibilityTest extends TestCase
{
    public function test_talred_cqrs_class_alias_uses_the_existing_zolta_implementation(): void
    {
        self::assertTrue(class_exists(Command::class));
        self::assertSame(
            \Zolta\Cqrs\Commands\Command::class,
            (new \ReflectionClass(Command::class))->getName(),
        );
    }

    public function test_talred_cqrs_interface_alias_uses_the_existing_zolta_contract(): void
    {
        self::assertTrue(interface_exists(CommandInterface::class));
        self::assertSame(
            \Zolta\Cqrs\Commands\Contracts\CommandInterface::class,
            (new \ReflectionClass(CommandInterface::class))->getName(),
        );
    }

    public function test_talred_cqrs_attribute_alias_uses_the_existing_zolta_attribute(): void
    {
        self::assertTrue(class_exists(HandlesCommand::class));
        self::assertSame(
            \Zolta\Cqrs\Attributes\HandlesCommand::class,
            (new \ReflectionClass(HandlesCommand::class))->getName(),
        );
    }

    public function test_talred_cqrs_trait_alias_uses_the_existing_zolta_trait(): void
    {
        self::assertTrue(trait_exists(HandlesDomainEvents::class));
        self::assertTrue(trait_exists(\Zolta\Cqrs\Events\Traits\HandlesDomainEvents::class));
    }

    public function test_talred_result_alias_preserves_existing_cqrs_behavior(): void
    {
        $result = Result::success(['status' => 'ok']);

        self::assertSame(['status' => 'ok'], $result->getValue());
        self::assertSame(\Zolta\Cqrs\Services\Result::class, $result::class);
    }
}
