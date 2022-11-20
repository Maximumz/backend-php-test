<?php

use Symfony\Component\Debug\Debug;
use \Routes\Routes;

require_once __DIR__.'/../vendor/autoload.php';

Debug::enable();

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/dev.php';

Routes::registerRoutes($app);

$app->run();
