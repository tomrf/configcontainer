<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer\Test;

use Tomrf\ConfigContainer\ConfigContainer;

/**
 * @internal
 * @coversNothing
 */
final class ConfigContainerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigContainer
     */
    private static $configContainer;

    public static function setUpBeforeClass(): void
    {
        static::$configContainer = new ConfigContainer();
        static::$configContainer->set('simple_key', 123);
        static::$configContainer->set('testing.nested_key', 'abc');
        static::$configContainer->set('testing.bool.true', true);
        static::$configContainer->set('testing.bool.false', false);
        static::$configContainer->setFromArray([
            'set_from_array' => 321,
            'testing.nested_set_from_array' => 'xyz',
            'testing.bool.true_from_array' => true,
        ]);
    }

    public function testConfigContainerIsInstanceOfConfigContainer(): void
    {
        static::assertIsObject(static::$configContainer);
        static::assertInstanceOf(
            ConfigContainer::class,
            static::$configContainer
        );
    }

    public function testGet(): void
    {
        static::assertSame(123, static::$configContainer->get('simple_key'));
        static::assertSame('abc', static::$configContainer->get('testing.nested_key'));
        static::assertTrue(static::$configContainer->get('testing.bool.true'));
        static::assertFalse(static::$configContainer->get('testing.bool.false'));
    }

    public function testGetKeysSetFromArray(): void
    {
        static::assertSame(321, static::$configContainer->get('set_from_array'));
        static::assertSame('xyz', static::$configContainer->get('testing.nested_set_from_array'));
        static::assertTrue(static::$configContainer->get('testing.bool.true_from_array'));
    }

    public function testQuery(): void
    {
        static::assertCount(7, static::$configContainer->query('*'));
        static::assertCount(3, static::$configContainer->query('*array'));
        static::assertArrayHasKey(
            'set_from_array',
            static::$configContainer->query('*array')
        );
        static::assertArrayHasKey(
            'testing.nested_set_from_array',
            static::$configContainer->query('test*array')
        );
        static::assertSame(
            ['testing.bool.true_from_array' => true],
            static::$configContainer->query('testing.*.*array')
        );
        static::assertSame(
            ['set_from_array' => 321],
            static::$configContainer->query('set_from_array')
        );
        static::assertSame(
            [],
            static::$configContainer->query('not_set')
        );
    }

    public function testFilterKeys(): void
    {
        $keys = static::$configContainer->filterKeys(
            '/(?:testing\\.)([\\w\\\\]+)(?:\\.|$)/'
        );
        static::assertContains('bool', $keys);
        static::assertContains('nested_key', $keys);
        static::assertContains('nested_set_from_array', $keys);
        static::assertCount(3, $keys);
    }

    public function testGetNode(): void
    {
        $root = static::$configContainer->getNode(null);
        static::assertArrayHasKey('simple_key', $root);
        static::assertArrayHasKey('testing', $root);

        $node = static::$configContainer->getNode('testing');
        static::assertArrayHasKey('bool', $node);
        static::assertArrayHasKey('nested_key', $node);
        static::assertArrayHasKey('nested_set_from_array', $node);

        $node = static::$configContainer->getNode('testing.bool');
        static::assertArrayHasKey('true', $node);
        static::assertArrayHasKey('false', $node);
        static::assertArrayHasKey('true_from_array', $node);

        $node = static::$configContainer->getNode('no.such.key');
        static::assertNull($node);
    }
}
