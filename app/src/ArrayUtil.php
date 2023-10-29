<?php

namespace App;

class ArrayUtil
{
    protected static $venv = [];
    public static function get(array $array, string|array $key = null, $default = null)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                $tmp = self::get($array, $k, null);
                if (!is_null($tmp)) {
                    return $tmp;
                }
            }
            return $default;
        }

        if (is_null($key) || empty($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        if (
            preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = self::get($array, $key);
                if (is_array($value)) {
                    $value = self::get($value, $key);
                }
                return $value;
            }, $key) !== $key
        ) {
            $key = preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = self::get($array, $key);
                if (is_array($value)) {
                    $value = self::get($value, $key);
                }
                return $value;
            }, $key);
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    public static function set(array &$array, string $key, $value)
    {
        if (is_null($key) || empty($key)) {
            return $array = $value;
        }

        if (
            preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = self::get($array, $key);
                if (is_array($value)) {
                    $value = self::get($value, $key);
                }
                return $value;
            }, $key) !== $key
        ) {
            $key = preg_replace_callback('/\{([^\}]+)\}/', function ($matches) use (&$array) {
                $key = $matches[1];
                $value = self::get($array, $key);
                if (is_array($value)) {
                    $value = self::get($value, $key);
                }
                return $value;
            }, $key);
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function has(array $array, string $key)
    {
        if (empty($array) || is_null($key) || empty($key)) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    public static function forget(array &$array, string $key)
    {
        if (is_null($key) || empty($key)) {
            return $array = [];
        }

        if (array_key_exists($key, $array)) {
            unset($array[$key]);
            return $array;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                return $array;
            }

            $array = &$array[$key];
        }

        unset($array[array_shift($keys)]);

        return $array;
    }

    public static function venv_set(string $key, $value): void
    {
        static::$venv[$key] = $value;
    }

    public static function venv_get(string $key = null, $default = null)
    {
        return self::get(static::$venv, $key, $default);
    }

    public static function venv_protect(): void
    {
        foreach ($_ENV as $key => $value) {
            self::venv_set($key, $value);
            unset($_ENV[$key]);
        }
    }
}
