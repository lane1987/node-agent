<?php
namespace Swoole\NodeAgent;

class Client extends Base
{
    /**
     * @var \swoole_client
     */
    protected $sock;
    public $errCode;

    function connect($host, $port, $timeout = 30)
    {
        $this->sock = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);
        $this->sock->set([
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,       //第N个字节是包长度的值
            'package_body_offset' => 4,       //第几个字节开始计算长度
            'package_max_length' => 2000000,  //协议最大长度
        ]);
        if ($this->sock->connect($host, $port, $timeout) === false)
        {
            $this->errCode = $this->sock->errCode;
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * 上传文件
     * @param $local_file
     * @param $remote_file
     * @param bool $override 是否覆盖文件
     * @return bool
     */
    function upload($local_file, $remote_file, $override = true)
    {
        $result = $this->request(array(
            'cmd' => 'upload',
            'size' => filesize($local_file),
            'override' => $override,
            'file' => $remote_file,
        ));
        //发送Header成功了，开始传输文件内容
        //文件按照8K分片发送
        if ($result['code'] == 0)
        {
            $fp = fopen($local_file, 'r');
            if (!$fp)
            {
                echo "Error: open $local_file failed.\n";
                return false;
            }
            while(!feof($fp))
            {
                $read = fread($fp, 8192);
                if ($read !== false)
                {
                    //发送文件内容，JSON不需要串化
                    $rs = $this->sock->send($this->pack($read, false));
                    if ($rs === false)
                    {
                        echo "transmission failed. socket error code {$this->sock->errCode}\n";
                        return false;
                    }
                }
                else
                {
                    echo "Error: read $local_file failed.\n";
                }
            }
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 删除一些文件
     * @param array $files
     * @return array
     */
    function delete(array $files)
    {
        return $this->request([
            'cmd' => 'delete',
            'files' => $files,
        ]);
    }


    /**
     * 删除一些文件
     * @param string $shell_script
     * @return array
     */
    function execute($shell_script)
    {
        return $this->request([
            'cmd' => 'execute',
            'shell_script' => $shell_script,
        ]);
    }

    /**
     * @param $data
     * @param bool $json 是否进行JSON串化
     * @return bool|mixed
     */
    protected function request($data)
    {
        $ret = $this->sock->send($this->pack($data));
        if ($ret === false)
        {
            fail:
            $this->errCode = $this->sock->errCode;
            return false;
        }
        $ret = $this->sock->recv();
        if (!$ret)
        {
            goto fail;
        }
        $json = json_decode(substr($ret, 4), true);
        //服务器端返回的内容不正确
        if (!isset($json['code']))
        {
            $this->errCode = 9001;
            return false;
        }
        return $json;
    }
}