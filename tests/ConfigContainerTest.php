<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer\Test;

use RuntimeException;
use Tomrf\ConfigContainer\ConfigContainer;

/**
 * @covers \Tomrf\ConfigContainer\ConfigContainer
 *
 * @internal
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
        static::assertInstanceOf(
            ConfigContainer::class,
            static::$configContainer
        );
    }

    public function testConstructWithInitialData(): void
    {
        $configContainer = new ConfigContainer([
            'initial' => [
                'int' => 100,
                'string' => 'a string',
                'bool' => true,
            ],
        ]);
        static::assertTrue($configContainer->get('initial.bool'));
        static::assertSame('a string', $configContainer->get('initial.string'));
    }

    public function testSet(): void
    {
        static::assertSame(123, static::$configContainer->set('set_int', 123));
        static::assertSame('string', static::$configContainer->set('set_string', 'string'));
        static::assertSame([], static::$configContainer->set('set_array', []));
        static::assertNull(static::$configContainer->set('set_null', null));
    }

    public function testGet(): void
    {
        static::assertSame(123, static::$configContainer->get('simple_key'));
        static::assertSame('abc', static::$configContainer->get('testing.nested_key'));
        static::assertTrue(static::$configContainer->get('testing.bool.true'));
        static::assertFalse(static::$configContainer->get('testing.bool.false'));
    }

    public function testGetWithDefault(): void
    {
        static::assertSame(123, static::$configContainer->get('simple_key'), 'default');
        static::assertSame('abc', static::$configContainer->get('testing.nested_key'), 'default');
        static::assertTrue(static::$configContainer->get('testing.bool.true'), 'default');
        static::assertFalse(static::$configContainer->get('testing.bool.false'), 'default');
    }

    public function testGetNonExistingWithDefault(): void
    {
        static::assertSame('default', static::$configContainer->get('x_simple', 'default'));
        static::assertSame('default', static::$configContainer->get('x_nested.nested', 'default'));
        static::assertTrue(static::$configContainer->get('x_default_bool_true', true));
        static::assertFalse(static::$configContainer->get('x_default_bool_false', false));
    }

    public function testGetKeysSetFromArray(): void
    {
        static::assertSame(321, static::$configContainer->get('set_from_array'));
        static::assertSame('xyz', static::$configContainer->get('testing.nested_set_from_array'));
        static::assertTrue(static::$configContainer->get('testing.bool.true_from_array'));
    }

    public function testSetOverwriteNested(): void
    {
        static::$configContainer->set('level1.level2.level3', 3);
        static::assertSame(3, static::$configContainer->get('level1.level2.level3'));

        static::$configContainer->set('level1.level2', 2);
        static::assertSame(2, static::$configContainer->get('level1.level2'));

        static::$configContainer->set('level1', 1);
        static::assertSame(1, static::$configContainer->get('level1'));
    }

    public function testSearch(): void
    {
        $keys = static::$configContainer->search(
            '/nested/'
        );
        static::assertArrayHasKey('testing.nested_key', $keys);
        static::assertArrayHasKey('testing.nested_set_from_array', $keys);
        static::assertCount(2, $keys);
    }

    public function testSearchWithIllegalRegexFails(): void
    {
        $this->expectException(RuntimeException::class);
        static::$configContainer->search(
            '/(/'
        );
    }

    public function testSearchFailsWhenIllegalDelimiter(): void
    {
        $this->expectException(RuntimeException::class);
        $keys = static::$configContainer->search(
            'illegal delimiter'
        );
    }

    public function testGetNode(): void
    {
        $node = static::$configContainer->get('testing');
        static::assertArrayHasKey('bool', $node);
        static::assertArrayHasKey('nested_key', $node);
        static::assertArrayHasKey('nested_set_from_array', $node);

        $node = static::$configContainer->get('testing.bool');
        static::assertArrayHasKey('true', $node);
        static::assertArrayHasKey('false', $node);
        static::assertArrayHasKey('true_from_array', $node);

        $node = static::$configContainer->get('no.such.key');
        static::assertNull($node);
    }

    public function testSearchWithEmptyQuery(): void
    {
        $this->expectException(RuntimeException::class);
        static::$configContainer->search('');
    }
}
