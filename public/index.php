<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Niang\Core\Application;

$app = new Application(dirname(__DIR__));

$app->loadRoutes(dirname(__DIR__) . '/routes/web.php');

$app->run();
