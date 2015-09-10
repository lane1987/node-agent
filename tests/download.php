<?php
require_once dirname(__DIR__) . '/src/_init.php';

$file = NodeAgent\Node::downloadPackage('http://183.57.37.213/node-agent/node-agent.phar');
echo strlen($file),"\n";
