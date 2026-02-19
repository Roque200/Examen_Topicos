<?php

namespace App\Classes;

use Illuminate\Support\Arr;

class Config
{
    protected $opts;

    public function __construct(array $opts = [])
    {
        $this->opts = $opts;
    }

    // Añadir método accessor para compatibilidad con PasswordResource->getOpts()
    public function getOpts(): array
    {
        // devolver 'opts' si existe
        if (property_exists($this, 'opts') && is_array($this->opts)) {
            return $this->opts;
        }

        // construir opciones desde propiedades conocidas si están presentes
        $opts = [];
        $known = ['length', 'use_upper', 'use_lower', 'use_digits', 'use_symbols', 'exclude_similar'];
        foreach ($known as $k) {
            if (property_exists($this, $k)) {
                $opts[$k] = $this->{$k};
            }
        }

        return $opts;
    }

    public function setOpts(array $opts): void
    {
        $this->opts = $opts;
    }

    public function get(string $key, $default = null): mixed
    {
        return $this->arrayGet($this->opts, $key, $default);
    }

    public function has(string $key): bool
    {
        return $this->arrayHas($this->opts, $key);
    }

    public function all(): array
    {
        return $this->opts;
    }

    private function arrayGet(array $array, string $key, $default = null)
    {
        if ($key === null || $key === '') {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    private function arrayHas(array $array, string $key): bool
    {
        if ($key === null || $key === '') {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        if (strpos($key, '.') === false) {
            return array_key_exists($key, $array);
        }

        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }
}