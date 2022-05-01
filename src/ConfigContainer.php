<?php

declare(strict_types=1);

namespace Tomrf\ConfigContainer;

use RuntimeException;

class ConfigContainer extends Container
{
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
     * @return array
     */
    public function query(string $query)
    {
        return $this->queryArray($query, $this->container);
    }

    /**
     * Set multiple keys from an array.
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
     * @throws RuntimeException
     */
    public function setPhpIniFromConfig(array $configArray): void
    {
        foreach ($this->flattenArray($configArray) as $key => $value) {
            $this->setPhpIni($key, $value);
        }
    }

    /**
     * Set PHP ini option.
     *
     * @throws RuntimeException
     */
    public function setPhpIni(string $key, mixed $value): void
    {
        try {
            if (false === ini_set($key, $value)) {
                throw new RuntimeException(sprintf('ini_set() failed for option "%s"', $key));
            }
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf(
                'Could not set PHP ini option "%s": %s',
                $key,
                $e->getMessage()
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
     * Get environment variable.
     *
     * @param mixed $key
     * @param mixed $default
     */
    public function env(string $key, mixed $default = null): mixed
    {
        if (!isset($_ENV[$key])) {
            return $default;
        }

        $value = $_ENV[$key];

        if ('true' === mb_strtolower($value) || 'false' === mb_strtolower($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOL);
        }

        return $value;
    }

    /**
     * Flatten an array with dotted hierarchy notation.
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
     * @return array<string,mixed>
     */
    private function queryArray(string $query, array $array)
    {
        return array_intersect_key($array, array_flip(
            preg_grep(sprintf(
                '/^%s$/i',
                str_replace('\\*', '.*?', preg_quote($query, '/'))
            ), array_keys($array))
        ));
    }
}
