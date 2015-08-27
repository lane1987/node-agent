<?php
require_once __DIR__ . '/Swoole/NodeAgent/Base.php';
$encrypt_key = md5('5wbRnDYuMzdjYffs');
echo $encrypt_key;exit;

//$des = new \Swoole\DES($encrypt_key);
//
//$data = $des->encode(json_encode(['code' => '400', 'file' => __FILE__, 'hello' => 'world']));
//var_dump($des->decode($data));
