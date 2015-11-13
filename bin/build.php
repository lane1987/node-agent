#!/usr/local/bin/php
<?php
require_once dirname(__DIR__) . '/src/_init.php';
if (empty($argv[1]))
{
    $dst = 'node';
}
else
{
    $dst = trim($argv[1]);
}

if ($dst == 'node')
{
    $pharFile = __DIR__ . '/node-agent.phar';
    unlink($pharFile);
    $phar = new Phar($pharFile);
    $phar->buildFromDirectory(WEBPATH, '/\.php$/');
    $phar->addFile(WEBPATH . '/encrypt.key', 'encrypt.key');
    $phar->compressFiles(\Phar::GZ);
    $phar->stopBuffering();
    $phar->setStub($phar->createDefaultStub('node.php'));
    echo "node-agent.phar打包成功\n";
}
elseif ($dst == 'center')
{
    $pharFile = __DIR__ . '/node-center.phar';
    unlink($pharFile);
    $phar = new Phar($pharFile);
    $phar->buildFromDirectory(WEBPATH, '/\.php$/');
    $phar->addFile(WEBPATH . '/encrypt.key', 'encrypt.key');
    $phar->compressFiles(\Phar::GZ);
    $phar->stopBuffering();
    $phar->setStub($phar->createDefaultStub('center.php'));
    echo "node-center.phar打包成功\n";
}
elseif ($dst == 'key')
{
    $encrypt_key = md5(uniqid('encrypt'));
    echo $encrypt_key;
}
elseif ($dst == 'upload_center')
{
    $encrypt_key = file_get_contents(WEBPATH . '/encrypt.key');
    $client = new NodeAgent\Client($encrypt_key);
    if (!$client->connect('183.57.37.213', 9507, 10))
    {
        echo "Error: connect to server failed. " . swoole_strerror($client->errCode);
        die("\n");
    }
    $r = $client->upload(__DIR__ . '/node-center.phar', '/data/node-agent/node-center.phar');
    if ($r)
    {
        echo "上传成功\n";
    }
}
elseif ($dst == 'upload_node')
{
    $encrypt_key = file_get_contents(WEBPATH . '/encrypt.key');
    $client = new NodeAgent\Client($encrypt_key);
    if (!$client->connect('183.57.37.213', 9507, 10))
    {
        echo "Error: connect to server failed. " . swoole_strerror($client->errCode);
        die("\n");
    }
    $r = $client->upload(__DIR__ . '/node-agent.phar', '/data/node-agent/node-agent.phar');
    if ($r)
    {
        echo "上传成功\n";
    }
}
