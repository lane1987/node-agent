<?php
namespace NodeAgent;

use Swoole;

class Center
{
    protected $serv;

    function onPacket($serv, $data, $addr)
    {

    }

    function run()
    {
        $serv = new \swoole_server("0.0.0.0", 9508, SWOOLE_BASE, SWOOLE_SOCK_UDP);

        $runtime_config = array(
            'worker_num' => 1,
        );

        global $argv;
        if (!empty($argv[1]) and $argv[1] == 'daemon')
        {
            $runtime_config['daemonize'] = true;
        }
        $serv->set($runtime_config);
        $serv->on('Start', function ($serv)
        {
            echo "Swoole Center Server running\n";
        });
        $serv->on('receive', array($this, 'onreceive'));
        $this->serv = $serv;
        $serv->start();
    }
}