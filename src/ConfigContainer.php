<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer;

use RuntimeException;

class ConfigContainer extends Container
{
    /**
     * @param array<string,mixed> $array
     */
    public function __construct(array $array = [])
    {
        $this->setFromArray($array);
    }

    /**
     * Get configuration value by key.
     */
    public function get(string $id, mixed $default = null): mixed
    {
        return $this->container[$id] ?? $default;
    }

    /**
     * Query configuration keys using regular expression. Returns array of
     * matching key-value pairs.
     *
     * @return array<string,mixed>
     */
    public function query(string $query)
    {
        return $this->queryArray($query, $this->container);
    }

    /**
     * Set multiple keys from an array.
     *
     * @param array<string,mixed> $array
     */
    public function setFromArray(array $array): void
    {
        foreach ($this->flattenArray($array) as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Set multiple PHP ini configuration options from an array.
     *
     * @param array<string,mixed> $configArray
     *
     * @throws RuntimeException
     */
    public function setPhpIniFromConfig(array $configArray): void
    {
        foreach ($this->flattenArray($configArray) as $key => $value) {
            if (!\is_bool($value) && !is_numeric($value) && !\is_string($value)) {
                continue;
            }
            $this->setPhpIni($key, $value);
        }
    }

    /**
     * Set PHP ini option.
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
     * Get PHP ini option.
     */
    public function getPhpIni(string $key): string|false
    {
        return \ini_get($key);
    }

    /**
     * Flatten an array with dotted hierarchy notation.
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
                $parentKey = $key;

                if ($parentPtr) {
                    $parentKey = $parentPtr.'.'.$key;
                }

                $flat = array_merge($flat, $this->flattenArray($value, $parentKey));
            } else {
                $configKey = $key;

                if ($parentPtr) {
                    $configKey = $parentPtr.'.'.$key;
                }

                $flat[$configKey] = $value;
            }
        }

        return $flat;
    }

    /**
     * Preg grep array keys.
     *
     * @param array<string,mixed> $array
     *
     * @return array<string,mixed>
     */
    private function queryArray(string $query, array $array)
    {
        $match = preg_grep(sprintf(
            '/^%s$/i',
            str_replace('\\*', '.*?', preg_quote($query, '/'))
        ), array_keys($array));

        if (false === $match) {
            return [];
        }

        return array_intersect_key($array, array_flip($match));
    }
}
