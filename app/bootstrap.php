<?php

// bootstrap.php is in the app root
define('APP_ROOT', dirname(__FILE__) . '/');

require_once APP_ROOT . 'config.php';

require_once APP_ROOT . 'class/AzUpload.php';

$app = new AzUpload();
$app->handleRequest();