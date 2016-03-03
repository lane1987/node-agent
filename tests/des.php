<?php
define('DIR', dirname(__DIR__) . '/src/');
require_once DIR. '/_init.php';

$encrypt_key = file_get_contents(DIR.'/encrypt.key');
$des = new \NodeAgent\Base($encrypt_key);
$data = $des->pack(['code' => '400', 'file' => __FILE__, 'hello' => 'world']);
var_dump($data);
var_dump($des->unpack($data));
echo "OK\n";
