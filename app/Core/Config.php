<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @param array<string, mixed> $items */
    private function __construct(private array $items)
    {
    }

    public static function load(): self
    {
        $items = [];

        foreach (glob(BASE_PATH . '/config/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $items[$key] = require $file;
        }

        return new self($items);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
