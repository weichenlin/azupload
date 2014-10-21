<?php

chdir(dirname(__FILE__));

require_once 'config.php';
require_once 'class/AzUpload.php';

$app = new AzUpload();
$app->handleRequest();