<?php
require_once dirname(__DIR__) . '/src/_init.php';

$r = $php->redis->set('node:version', '{"version": "1.0.1", "hash": "", "url" : ""}');
//$r = Swoole\String::versionCompare('1.3.0', '1.2.0');
//$r = NodeAgent\Node::getDiskInfo();
var_dump($r);
