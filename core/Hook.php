<?php

class Hook
{
    private static array $hooks = [];

    public static function add($name, callable $callback)
    {
        self::$hooks[$name][] = $callback;
    }

    public static function run($name, $data = null)
    {
        if (!isset(self::$hooks[$name])) {
            return $data;
        }

        foreach (self::$hooks[$name] as $callback) {
            $data = $callback($data);
        }

        return $data;
    }
}