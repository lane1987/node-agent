<?php
namespace NodeAgent;

use Swoole;

/**
 * 节点服务器
 * Class Node
 * @package NodeAgent
 */
class Node extends Server
{
    /**
     * 版本号
     */
    const VERSION = '1.0.7';

    /**
     * phar包的绝对路径
     */
    protected $pharFile;
    protected $pharHash;

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
            swoole_event_add($this->centerSocket->sock, [$this, 'onPacket']);
        });
        $this->log(__CLASS__ . '-' . self::VERSION . ' is running.');
    }

    function setCenterSocket($ip, $port)
    {
        $this->centerSocket = new \swoole_client(SWOOLE_SOCK_UDP);
        $this->centerSocket->connect($ip, $port);
        $this->centerHost = $ip;
        $this->centerPort = $port;
    }

    function setPharInfo($file)
    {
        $this->pharFile = str_replace('phar://', '', $file);
        $this->pharHash = md5_file($this->pharFile);
    }

    function onPacket($sock)
    {
        $data = $this->centerSocket->recv();
        $req = unserialize($data);
        if (empty($req['cmd']))
        {
            $this->log("error packet");
            return;
        }
        if ($req['cmd'] == 'getInfo')
        {
            $this->centerSocket->send(serialize([
                //心跳
                'cmd' => 'putInfo',
                'info' => [
                    //机器HOSTNAME
                    'hostname' => gethostname(),
                    'ipList' => swoole_get_local_ip(),
                    'uname' => php_uname(),
                    'version' => self::VERSION,
                    'deviceInfo' => [
                        'cpu' => self::getCpuInfo(),
                        'mem' => self::getMemInfo(),
                        'disk' => self::getDiskInfo(),
                    ],
                ],
            ]));
        }
        //升级此phar包
        elseif ($req['cmd'] == 'upgrade')
        {
            if (empty($req['url']) or empty($req['hash']))
            {
                $this->log("缺少URL和hash");
            }

            $file = self::downloadPackage($req['url']);
            if ($file)
            {
                $hash = md5($file);
                //hash对比一致，可以更新
                if ($hash == $req['hash'])
                {
                    //更新phar包
                    file_put_contents($this->pharFile, $file);
                    $this->log("upgrade to ".$req['version']);
                    //退出进程，等待重新拉起
                    exit;
                }
            }
            else
            {
                $this->log("upgrade failed. Cannot fetch url [{$req['url']}]");
            }
        }
    }

    /**
     * 获取CPU信息
     * @return array
     */
    static function getCpuInfo()
    {
        $cpu = file_get_contents('/proc/cpuinfo');
        $n = preg_match_all('$processor\s+\:\s+(\d+)$i', $cpu, $match);
        preg_match('$model name\s+\:\s+(.+)$i', $cpu, $match2);
        return ['processor_cnum' => $n, 'model' => $match2[1]];
    }

    /**
     * 获取内存信息
     * @return array
     */
    static function getMemInfo()
    {
        $data = file_get_contents('/proc/meminfo');
        preg_match('/MemTotal:\s+(\d+) kb/i', $data, $match1);
        preg_match('/MemAvailable:\s+(\d+) kb/i', $data, $match2);
        return ['total' => $match1[1] / 1024 / 1024, 'free' => $match2[1] / 1024 / 1024];
    }

    /**
     * 获取磁盘信息，使用df -h命令，单位为G
     * @return array
     */
    static function getDiskInfo()
    {
        $info = shell_exec('df -h');
        $n = preg_match_all('#/dev/(sd[a-z]{1}\d{1})\s+(\d+)g\s+(\d+)g\s+(\d+)g#ui', $info, $match);
        $result = array();
        if ($n > 0)
        {
            $total = 0;
            $avail = 0;
            for ($i = 0; $i < $n; $i++)
            {
                $total += $match[2][$i];
                $avail += $match[4][$i];
                $result[$match[1][$i]] = [
                    'total' => $match[2][$i],
                    'avail' => $match[4][$i],
                ];
            }
            $result['all']['total'] = $total;
            $result['all']['avail'] = $avail;
        }
        return $result;
    }

    static function downloadPackage($url)
    {
        $curl = new Swoole\Client\CURL;
        $curl->setCredentials('admin', 'aiQuee7e');
        return $curl->get($url);
    }

    function onTimer($id)
    {
        $this->centerSocket->send(serialize([
            //心跳
            'cmd' => 'heartbeat',
        ]));
    }
}