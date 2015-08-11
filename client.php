#!/usr/local/bin/php
<?php
/**
 * usage: php upload_client.php -h 127.0.0.1 -p 9507 -f test.jpg
 */
require_once __DIR__ . '/Swoole/NodeAgent/Base.php';
require_once __DIR__ . '/Swoole/NodeAgent/Client.php';

$encrypt_key = 'f05dbd87f09a1843c6579aa47e65019b7e089738187c791ab9db2fff0530fa97fb57e5ff8222c3e360521d1f6429fa8f';
$client = new \Swoole\NodeAgent\Client($encrypt_key);
$args = getopt("p:h:f:t");

if (empty($args['h']) or empty($args['f'])) 
{
    echo "Usage: php {$argv[0]} -h server_ip -p server_port -f file -t timeout\n";
    exit;
}

if (empty($args['p']))
{
	$args['p'] = 9507;
}

if (empty($args['t'])) 
{
    $args['t'] = 30;
}

$file = $args['f'];
if (!is_file($file))
{
    die("Error: file '{$args['f']}' not found\n");
}

/**
 * 连接到服务器
 */
if (!$client->connect($args['h'], $args['p'], $args['t']))
{
    echo "Error: connect to server failed. " . swoole_strerror($client->errCode);
    die("\n");
}

$remote_file = '/data/testnode/' . basename($file);
if (!$client->upload($file, $remote_file))
{
    die("upload success.\n");
}
//var_dump($client->delete(['/tmp/test1.txt', '/tmp/test2.txt']));