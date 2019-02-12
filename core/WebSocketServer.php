#!/usr/bin/php
<?php

require '../vendor/autoload.php';

define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
define('APP_PATH', __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);

use core\WS;

$ws = new WS();

if (!$ws->socket) {
    die("$ws->errStr ($ws->errNo)" . PHP_EOL);
}

$ws->run();