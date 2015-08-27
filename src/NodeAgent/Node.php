<?php
namespace NodeAgent;

/**
 * 节点服务器
 * Class Node
 * @package NodeAgent
 */
class Node extends Server
{
    /**
     * @var \swoole_client
     */
    protected $centerSocket;

    protected $centerHost;
    protected $centerPort;

    function init()
    {
        $this->serv->on('WorkerStart', function (\swoole_server $serv, $worker_id)
        {
            //每1分钟向服务器上报
            $serv->tick(60000, [$this, 'onTimer']);
        });
    }

    function setCenterSocket($ip, $port)
    {
        $this->centerSocket = new \swoole_client(SWOOLE_SOCK_UDP);
        $this->centerSocket->connect($ip, $port);
        $this->centerHost = $ip;
        $this->centerPort = $port;
    }

    function onPacket($serv, $data, $addr)
    {
        if ($addr['address'] != $this->centerHost)
        {
            $this->log("{$addr['address']} is not center server host.");
        }
    }

    function onTimer($id)
    {
        $this->centerSocket->send(serialize([
            //心跳
            'cmd' => 'heartbeat',
            //机器HOSTNAME
            'name' => gethostname(),
            'ip' => swoole_get_local_ip(),
            'uname' => php_uname(),
        ]));
    }
}