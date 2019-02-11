<?php

namespace core;

class App
{
    /**
     * @var \core\Router
     */
    public static $router;

    public static $db;

    /**
     * @var \core\Kernel
     */
    public static $kernel;

    public static function init()
    {
        static::$router = new Router();
        static::$kernel = new Kernel();
        static::$db = new DB();

        try {
            self::$kernel->launch();
        } catch (\Exception $e) {
            //
        }
    }
}