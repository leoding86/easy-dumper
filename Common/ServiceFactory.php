<?php
namespace Common;

class ServiceFactory
{
    public static function get($type, $args)
    {
        $service = '\\Service\\' . $type . '\\Service';
        return new $service($args);
    }
}