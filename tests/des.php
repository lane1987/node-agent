<?php
require_once dirname(__DIR__) . '/src/NodeAgent/Base.php';
$encrypt_key = md5(uniqid('encrypt'));
echo $encrypt_key;
exit;

//$des = new \Swoole\DES($encrypt_key);
//
//$data = $des->encode(json_encode(['code' => '400', 'file' => __FILE__, 'hello' => 'world']));
//var_dump($des->decode($data));
