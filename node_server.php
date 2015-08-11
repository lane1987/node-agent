<?php
require_once __DIR__ . '/Swoole/NodeAgent/Base.php';
require_once __DIR__ . '/Swoole/NodeAgent/Server.php';

$encrypt_key = 'f05dbd87f09a1843c6579aa47e65019b7e089738187c791ab9db2fff0530fa97fb57e5ff8222c3e360521d1f6429fa8f';
//$encrypt_key = '';
$svr = new Swoole\NodeAgent\Server($encrypt_key);
//设置上传文件的存储目录
$svr->setRootPath('/data/testnode/');
//设置允许上传的文件最大尺寸
$svr->setMaxSize(100 * 1024 * 1024);
$svr->setCenterServer('127.0.0.1', 9506);
$svr->run();
