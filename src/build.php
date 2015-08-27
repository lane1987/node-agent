<?php
define('WEBPATH', __DIR__);
require_once '/data/www/public/framework/libs/lib_config.php';

$phar = new \Phar('node-agent.phar');
$phar->buildFromDirectory(__DIR__, '/\.php$/');
$phar->addFile('encrypt.key');
$phar->compressFiles(\Phar::GZ);
$phar->stopBuffering();
$phar->setStub($phar->createDefaultStub( __DIR__, 'node_server.php'));
