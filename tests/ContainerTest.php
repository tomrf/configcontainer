<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer\Test;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use Tomrf\ConfigContainer\Container;
use Tomrf\ConfigContainer\NotFoundException;

/**
 * @covers \Tomrf\ConfigContainer\Container
 *
 * @internal
 */
final class ContainerTest extends \PHPUnit\Framework\TestCase
{
    public static function setUpBeforeClass(): void
    {
    }

    public function testContainerIsInstanceOfContainerImplementsContainerInterface(): void
    {
        static::assertInstanceOf(Container::class, new Container());
        static::assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function testSetGet(): void
    {
        $container = new Container();

        $container->set('string', 'a string');
        $container->set('int', 10);
        $container->set('bool', true);
        $container->set('object', new stdClass());

        static::assertSame('a string', $container->get('string'));
        static::assertSame(10, $container->get('int'));
        static::assertTrue($container->get('bool'));
        static::assertIsObject($container->get('object'));
    }

    public function testContainerWithInitialContent(): void
    {
        $container = new Container([
            'string' => 'a string',
            'int' => PHP_INT_MAX,
            'bool' => true,
            'object' => new stdClass(),
        ]);

        static::assertsame('a string', $container->get('string'));
        static::assertsame(PHP_INT_MAX, $container->get('int'));
        static::assertsame(true, $container->get('bool'));
        static::assertIsObject($container->get('object'));
    }

    public function testContainerHas(): void
    {
        $container = new Container([
            'string' => 'a string',
            'bool' => true,
        ]);

        static::assertTrue($container->has('string'));
        static::assertTrue($container->has('bool'));
        static::assertFalse($container->has('int'));
        static::assertFalse($container->has('object'));
        static::assertFalse($container->has(''));
    }

    public function testContainerGetThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectException(NotFoundExceptionInterface::class);

        $container = new Container();
        $container->get('key');
    }
}
