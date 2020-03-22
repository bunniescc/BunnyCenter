<?php
define('APP_PATH', __DIR__ . '/');
define('APP_DEBUG', true);
define("IN_TWIMI_PHP", "True", TRUE);
date_default_timezone_set('PRC');
require('vendor/autoload.php');
(new BunnyPHP\BunnyPHP())->run();