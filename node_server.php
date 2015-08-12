<?php
require_once __DIR__ . '/Swoole/NodeAgent/Base.php';
require_once __DIR__ . '/Swoole/NodeAgent/Server.php';

//是一个96字节的文件
$encrypt_key = file_get_contents(__DIR__.'/encrypt.key');
$svr = new Swoole\NodeAgent\Server($encrypt_key);
//设置上传文件的存储目录
$dir = ['/data/www', '/data/system', '/etc/nginx'];
$svr->setRootPath('/data/testnode/');
//设置允许上传的文件最大尺寸
$svr->setMaxSize(100 * 1024 * 1024);
$svr->setCenterServer('127.0.0.1', 9506);
$svr->run();
