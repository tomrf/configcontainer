<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer;

use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class ConfigContainer extends Container implements ContainerInterface
{
    /**
     * @param array<string,mixed> $array
     */
    public function __construct(array $array = [])
    {
        $this->setFromArray($array);
    }

    /**
     * Set multiple keys from an array.
     *
     * @param array<string,mixed> $array
     */
    public function setFromArray(array $array): void
    {
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Set configuration key.
     */
    public function set(string $id, mixed $value): mixed
    {
        $ptr = &$this->container;
        $parts = explode('.', $id);

        foreach ($parts as $part) {
            if (!\is_array($ptr)) {
                continue;
            }
            if (!isset($ptr[$part])) {
                $ptr[$part] = [];
            }

            $ptr = &$ptr[$part];
        }

        return $ptr = $value;
    }

    /**
     * Get configuration value by key.
     */
    public function get(string $id, mixed $default = null): mixed
    {
        $ptr = &$this->container;
        $parts = explode('.', $id);

        foreach ($parts as $part) {
            if (!\is_array($ptr)) {
                continue;
            }
            if (!isset($ptr[$part])) {
                return $default;
            }

            $ptr = &$ptr[$part];
        }

        return $ptr;
    }

    /**
     * Query configuration keys.
     *
     * @return array<string,mixed>
     */
    public function query(string $query)
    {
        $array = $this->flattenArray($this->container);

        $match = preg_grep(sprintf(
            '/^%s$/i',
            str_replace('\\*', '.*?', preg_quote($query, '/'))
        ), array_keys($array));

        if (false === $match) {
            return [];
        }

        return array_intersect_key($array, array_flip($match));
    }

    /**
     * Filter configuration keys using regex, returing an array of matches across
     * all set keys.
     *
     * @return array<string>
     */
    public function filterKeys(string $regex): array
    {
        $array = $this->flattenArray($this->container);
        $results = [];

        foreach (array_keys($array) as $key) {
            preg_match($regex, $key, $matches);
            if (isset($matches[1])) {
                $results[(string) $matches[1]] = true;
            }
        }

        return array_keys($results);
    }

    /**
     * Set multiple PHP ini configuration options from the container using a
     * query as filter.
     *
     * @throws RuntimeException
     */
    public function setPhpIniFromNode(string $id): void
    {
        $node = $this->getNode($id);

        if (null === $node) {
            throw new RuntimeException('Node "%s" not found in configuration tree');
        }

        $array = $this->flattenArray($node);
        foreach ($array as $key => $value) {
            if (!\is_scalar($value)) {
                continue;
            }
            $this->setPhpIni($key, $value);
        }
    }

    /**
     * Set PHP ini option using ini_set().
     *
     * @throws RuntimeException
     */
    public function setPhpIni(string $key, string|int|float|bool $value): void
    {
        try {
            if (false === ini_set($key, (string) $value)) {
                throw new RuntimeException(sprintf('ini_set() failed for option "%s"', $key));
            }
        } catch (\Exception $exception) {
            throw new RuntimeException(sprintf(
                'Could not set PHP ini option "%s": %s',
                $key,
                $exception->getMessage()
            ));
        }
    }

    /**
     * Returns a node (and its children) from the tree as a nested array.
     *
     * Returns the root node (the whole tree) if no node id is specified,
     * null if the node does not exist.
     *
     * @return null|array<string,mixed>
     */
    public function getNode(?string $id): ?array
    {
        if (null === $id) {
            return $this->container;
        }

        $ptr = &$this->container;
        $parts = explode('.', $id);

        foreach ($parts as $part) {
            if (!\is_array($ptr)) {
                continue;
            }
            if (!isset($ptr[$part])) {
                return null;
            }

            $ptr = &$ptr[$part];
        }

        return $ptr;
    }

    /**
     * Flatten an array.
     *
     * @param array<string,mixed> $arrayPtr
     *
     * @return array<string,mixed>
     */
    private function flattenArray(array $arrayPtr, mixed $parentPtr = null): array
    {
        $flat = [];

        foreach ($arrayPtr as $key => $value) {
            if (\is_array($value)) {
                $flat = array_merge($flat, $this->flattenArray(
                    $value,
                    null === $parentPtr
                        ? $key
                        : sprintf(
                            '%s.%s',
                            \is_scalar($parentPtr) ? $parentPtr : '',
                            $key
                        )
                ));

                continue;
            }
            if ($parentPtr) {
                $key = sprintf(
                    '%s.%s',
                    \is_scalar($parentPtr) ? $parentPtr : '',
                    $key
                );
            }

            $flat[$key] = $value;
        }

        return $flat;
    }
}
