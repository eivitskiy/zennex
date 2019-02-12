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
//            exit($e->getMessage());

            header("HTTP/1.0 404 Not Found");
            echo file_get_contents(APP_PATH . 'views/404.html');
            exit();
        }
    }
}