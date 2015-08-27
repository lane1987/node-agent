<?php
define('WEBPATH', __DIR__);
require_once __DIR__ . '/framework/libs/lib_config.php';
Swoole\Loader::addNameSpace('NodeAgent', __DIR__ . '/NodeAgent');