<?php
define('WEBPATH', dirname(__DIR__) . '/src');
require_once '/data/www/public/framework/libs/lib_config.php';

$phar = new \Phar(__DIR__.'/node-agent.phar');
$phar->buildFromDirectory(WEBPATH, '/\.php$/');
$phar->addFile(WEBPATH.'/encrypt.key', 'encrypt.key');
$phar->compressFiles(\Phar::GZ);
$phar->stopBuffering();
$phar->setStub($phar->createDefaultStub('node_server.php'));
