<?php

declare(strict_types=1);

namespace Tomrf\Phpskel;

use Tomrf\ConfigContainer\ConfigContainer;

/**
 * @internal
 * @coversNothing
 */
final class ConfigContainerTest extends \PHPUnit\Framework\TestCase
{
    private static $configContainer;

    public static function setUpBeforeClass(): void
    {
        static::$configContainer = new ConfigContainer();
    }

    public function testConfigContainerIsInstanceOfConfigContainer(): void
    {
        static::assertIsObject(static::$configContainer);
        static::assertInstanceOf(ConfigContainer::class, static::$configContainer);
    }

    public function testSet(): void
    {
        static::assertSame(123, $this->configContainer()->set('simple_key', 123));
        static::assertSame('abc', $this->configContainer()->set('testing.nested_key', 'abc'));
        static::assertTrue($this->configContainer()->set('testing.bool.true', true));
        static::assertFalse($this->configContainer()->set('testing.bool.false', false));
    }

    public function testGet(): void
    {
        static::assertSame(123, $this->configContainer()->get('simple_key'));
        static::assertSame('abc', $this->configContainer()->get('testing.nested_key'));
        static::assertTrue($this->configContainer()->get('testing.bool.true'));
        static::assertFalse($this->configContainer()->get('testing.bool.false'));
    }

    public function testSetFromArray(): void
    {
        $this->configContainer()->setFromArray([
            'set_from_array' => 321,
            'testing.nested_set_from_array' => 'xyz',
            'testing.bool.true_from_array' => true,
        ]);

        static::assertSame(321, $this->configContainer()->get('set_from_array'));
        static::assertSame('xyz', $this->configContainer()->get('testing.nested_set_from_array'));
        static::assertTrue($this->configContainer()->get('testing.bool.true_from_array'));
    }

    public function testQuery(): void
    {
        static::assertCount(7, $this->configContainer()->query('*'));
        static::assertCount(3, $this->configContainer()->query('*array'));
        static::assertArrayHasKey('set_from_array', $this->configContainer()->query('*array'));
        static::assertArrayHasKey('testing.nested_set_from_array', $this->configContainer()->query('test*array'));
        static::assertSame(['testing.bool.true_from_array' => true], $this->configContainer()->query('testing.*.*array'));
        static::assertSame(['set_from_array' => 321], $this->configContainer()->query('set_from_array'));
        static::assertSame([], $this->configContainer()->query('not_set'));
    }

    private function configContainer(): ConfigContainer
    {
        return static::$configContainer;
    }
}
