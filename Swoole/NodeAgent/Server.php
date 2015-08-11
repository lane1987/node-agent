<?php
namespace Swoole\NodeAgent;

class Server extends Base
{
    /**
     * @var \swoole_server
     */
    protected $serv;
    protected $files;

    protected $center_server;
    protected $max_file_size = 100000000; //100M

    /**
     * 限定上传文件的根目录
     * @var string
     */
    protected $root_path;

    function onConnect($serv, $fd, $from_id)
    {
        echo "new upload client[$fd] connected.\n";
    }

    /**
     * 发送回应
     * @param $fd
     * @param $code
     * @param $msg
     * @return bool
     */
    protected function sendResult($fd, $code, $msg)
    {
        $_data = $this->pack(array('code' => $code, 'msg' => $msg));
        $this->serv->send($fd, pack('N', strlen($_data)) . $_data);
        if (is_string($msg))
        {
            echo "[-->$fd]\t$code\t$msg\n";
        }
        if ($code != 0)
        {
            $this->serv->close($fd);
        }
        return true;
    }

    /**
     * 执行一段Shell脚本
     * @param $fd
     * @param $req
     */
    function _cmd_execute($fd, $req)
    {
        if (empty($req['shell_script']))
        {
            $this->sendResult($fd, 500, 'require shell_script.');
            return;
        }
        if (!is_file($req['shell_script']))
        {
            $this->sendResult($fd, 404, 'shell_script ['.$req['shell_script'].'] is not exist.');
            return;
        }
        $output = '';
        exec($req['shell_script'], $output, $code);
        if ($code == 0)
        {
            $this->sendResult($fd, 0, $output);
        }
        else
        {
            $this->sendResult($fd, 505, $output);
        }
    }

    /**
     * 删除文件
     * @param $fd
     * @param $req
     * @return bool
     */
    function _cmd_delete($fd, $req)
    {
        if (empty($req['files']))
        {
            $this->sendResult($fd, 500, 'require files.');
            return;
        }
        $delete_count = 0;
        foreach ($req['files'] as $f)
        {
            if (is_file($f) and unlink($f))
            {
                $delete_count++;
            }
        }
        $this->sendResult($fd, 0, 'delete '.$delete_count.' files.');
    }

    /**
     * 上传文件指令
     * @param $fd
     * @param $req
     * @return bool
     */
    protected function _cmd_upload($fd, $req)
    {
        if (empty($req['size']) or empty($req['file']))
        {
            return $this->sendResult($fd, 500, 'require dst_file and size.');
        }
        elseif ($req['size'] > $this->max_file_size)
        {
            return $this->sendResult($fd, 501, 'over the max_file_size. ' . $this->max_file_size);
        }

        $file = $req['file'];
        if (strncmp($file, $this->root_path, strlen($this->root_path)) != 0)
        {
            return $this->sendResult($fd, 502, "file path[{$file}] error. Access deny.");
        }
        elseif (!$req['override'] and is_file($file))
        {
            return $this->sendResult($fd, 503, 'file is exists, no override');
        }
        $fp = fopen($file, 'w');
        if (!$fp)
        {
            return $this->sendResult($fd, 504, "can open file[{$file}].");
        }
        else
        {
            $this->sendResult($fd, 0, 'transmission start');
            $this->files[$fd] = array('fp' => $fp, 'file' => $file, 'size' => $req['size'], 'recv' => 0);
        }
    }

    function onReceive(\swoole_server $serv, $fd, $from_id, $_data)
    {
        //文件传输尚未开始
        if (empty($this->files[$fd]))
        {
            $req = $this->unpack($_data);
            if ($req === false or empty($req['cmd']))
            {
                $this->sendResult($fd, 400, 'Error Request');
                return;
            }

            $func = '_cmd_'.$req['cmd'];
            if (is_callable([$this, $func]))
            {
                call_user_func([$this, $func], $fd, $req);
            }
            else
            {
                $this->sendResult($fd, 404, 'Command Not Support.');
                return;
            }
        }
        //传输已建立
        else
        {
            //直接接收数据，不需要解析json
            $data = $this->unpack($_data, false);
            $info = &$this->files[$fd];
            $fp = $info['fp'];
            $file = $info['file'];
            if (!fwrite($fp, $data))
            {
                $this->sendResult($fd, 600, "fwrite failed. transmission stop.");
                //关闭文件句柄
                fclose($this->files[$fd]['fp']);
                unlink($file);
            }
            else
            {
                $info['recv'] += strlen($data);
                if ($info['recv'] >= $info['size'])
                {
                    $this->sendResult($fd, 0, "Success, transmission finish. Close connection.");
                    //关闭句柄
                    fclose($this->files[$fd]['fp']);
                    unset($this->files[$fd]);
                }
            }
        }
    }

    function setRootPath($path)
    {
        if (!is_dir($path))
        {
            throw new \Exception(__METHOD__.": $path is not exists.");
        }
        $this->root_path = $path;
    }

    function setMaxSize($max_file_size)
    {
        $this->max_file_size = (int)$max_file_size;
        if ($this->max_file_size <= 0)
        {
            throw new \Exception(__METHOD__.": max_file_size is zero.");
        }
    }

    function setCenterServer($ip, $port)
    {
        $this->center_server = new \swoole_client(SWOOLE_SOCK_UDP);
        $this->center_server->connect($ip, $port);
    }

    function onclose($serv, $fd, $from_id)
    {
        unset($this->files[$fd]);
        echo "upload client[$fd] closed.\n";
    }

    function run()
    {
        $serv = new \swoole_server("0.0.0.0", 9507, SWOOLE_BASE);

        $runtime_config = array(
            'worker_num' => 1,
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,       //第N个字节是包长度的值
            'package_body_offset' => 4,       //第几个字节开始计算长度
            'package_max_length' => 2000000,  //协议最大长度
        );

        global $argv;
        if (!empty($argv[1]) and $argv[1] == 'daemon')
        {
            $runtime_config['daemonize'] = true;
        }
        $serv->set($runtime_config);
        $serv->on('Start', function ($serv)
        {
            echo "Swoole Upload Server running\n";
        });

        $this->root_path = rtrim($this->root_path, ' /');
        $serv->on('connect', array($this, 'onConnect'));
        $serv->on('receive', array($this, 'onreceive'));
        $serv->on('close', array($this, 'onclose'));
        $this->serv = $serv;
        $serv->start();
    }
}
