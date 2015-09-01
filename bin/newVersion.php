<?php
include "phar://node-agent.phar/_init.php";

$info = json_decode($php->redis->get(NodeAgent\Center::KEY_NODE_VERSION), true);
if (Swoole\String::versionCompare($info['version'], NodeAgent\Node::VERSION) < 0)
{
    echo "NodeAgent new version:" . NodeAgent\Node::VERSION . "\n";
    echo "Upload to NodeCenter\n";
    copy(__DIR__ . '/node-agent.phar', '/data/www/wwwroot/node-agent.' . NodeAgent\Node::VERSION . '.phar');

    $php->redis->set(NodeAgent\Center::KEY_NODE_VERSION, json_encode([
        'version' => NodeAgent\Node::VERSION,
        'hash' => md5_file('node-agent.phar'),
        'url' => 'http://192.168.0.138/node-agent.' . NodeAgent\Node::VERSION . '.phar',
    ]));
    echo "Done!\n";
}
else
{
    echo "The current version is newest [" . NodeAgent\Node::VERSION . "].\n";
}
