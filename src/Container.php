<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Container implements \Psr\Container\ContainerInterface
{
    /** @var array<string,mixed> */
    protected array $container = [];

    /**
     * @param array<string,mixed> $initialContent
     */
    public function __construct(array $initialContent = [])
    {
        foreach ($initialContent as $id => $value) {
            $this->set($id, $value);
        }
    }

    public function get(string $id): mixed
    {
        if (!isset($this->container[$id])) {
            throw new NotFoundException('Container does not contain '.$id);
        }

        return $this->container[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->container[$id]);
    }

    public function set(string $id, mixed $value): mixed
    {
        $this->container[$id] = $value;

        return $value;
    }
}
