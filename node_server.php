<?php
require_once __DIR__ . '/Swoole/NodeAgent/Server.php';
$encrypt_key = md5('5wbRnDYuMzdjYffs');
$svr = new Swoole\NodeAgent\Server($encrypt_key);
//设置上传文件的存储目录
$svr->setRootPath('/data/testnode/');
//设置允许上传的文件最大尺寸
$svr->setMaxSize(100 * 1024 * 1024);
$svr->setCenterServer('127.0.0.1', 9506);
$svr->run();
