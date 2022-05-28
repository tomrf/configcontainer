<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer;

use Exception;
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
            if (!isset($ptr[$part])) {
                $ptr[$part] = [];
            }

            if (\is_array($ptr[$part])) {
                $ptr = &$ptr[$part];
            }
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
            if (!isset($ptr[$part])) {
                return $default;
            }

            if (\is_array($ptr[$part])) {
                $ptr = &$ptr[$part];

                continue;
            }

            return $ptr[$part];
        }

        return $ptr;
    }

    /**
     * Returns an array of matching configuration keys using regex matching
     * on key names.
     *
     * @return array<string, mixed>
     */
    public function search(string $regularExpression): array
    {
        $array = $this->flattenArray($this->container);

        if (!isset($regularExpression[0])) {
            throw new RuntimeException(
                'Illegal regular expression'
            );
        }

        if (ctype_alnum($regularExpression[0]) || '\\' === $regularExpression[0]) {
            throw new RuntimeException(
                'Illegal regex delimiter, must not be alphanumeric or backslash'
            );
        }

        foreach (array_keys($array) as $key) {
            try {
                $numMatches = preg_match($regularExpression, $key);
            } catch (Exception $exception) {
                throw new RuntimeException(
                    sprintf('preg_match exception: %s', $exception)
                );
            }

            if (0 === $numMatches) {
                unset($array[$key]);
            }
        }

        return $array;
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
                        : sprintf('%s.%s', is_scalar($parentPtr) ? $parentPtr : '', $key)
                ));

                continue;
            }

            if ($parentPtr) {
                $key = sprintf('%s.%s', is_scalar($parentPtr) ? $parentPtr : '', $key);
            }

            $flat[$key] = $value;
        }

        return $flat;
    }
}
