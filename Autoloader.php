<?php

class Autoloader
{
    private static $loader = null;
    private static $loaded = [];

    public static function getLoader()
    {
        if (self::$loader != null) {
            return self::$loader;
        }

        self::$loader = new Autoloader();
        self::$loader->register();

        return self::$loader;
    }

    public function register($prepend = false)
    {
        spl_autoload_register([$this, 'loader'], true, $prepend);
    }

    public function loader($classname)
    {
        $parts = explode('\\', $classname);
        $file = ROOT . '/' . implode('/', $parts) . '.php';

        if (is_file($file)) {
            return require_once($file);
        }

        $last_part = $parts[count($parts) - 1];
        if (preg_match('/^I[A-Z]/', $last_part)) {
            $file = ROOT . '/Interface/' . $last_part . '.php';
            if (is_file($file)) {
                return require_once($file);
            }
        }
    }
}
