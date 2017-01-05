<?php
namespace Common;

class Helper
{
    public static function parseArgv($argv)
    {
        if (count($argv) == 1) {
            return [];
        } else {
            $matches = [];
            preg_match_all(
                '/-([a-z]+)\s+([^\s]+)/i',
                implode(' ', array_slice($argv, 1)),
                $matches,
                PREG_SET_ORDER
            );

            if (empty($matches)) {
                return [];
            }

            $args = [];
            foreach ($matches as $match) {
                $args[$match[1]] = $match[2];
            }
            return $args;
        }
    }

    public static function println($string)
    {
        vprintf($string . PHP_EOL, array_slice(func_get_args(), 1));
    }

    public static function printlnExit($string)
    {
        self::println($string, func_get_args());exit;
    }
}