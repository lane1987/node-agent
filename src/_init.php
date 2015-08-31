<?php
define('WEBPATH', __DIR__);
require_once __DIR__ . '/framework/libs/lib_config.php';

$env = get_cfg_var('env.name');
if (empty($env))
{
    $env = 'product';
}
define('ENV_NAME', $env);

Swoole::$php->config->setPath(__DIR__ . '/apps/configs/' . ENV_NAME);
Swoole\Loader::addNameSpace('NodeAgent', __DIR__ . '/NodeAgent');
