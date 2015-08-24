<?php
namespace Swoole\NodeAgent;

class Client extends Base
{
    /**
     * 上传回调函数
     * @var callable
     */
    public $UploadCallback;

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
        $file_size = filesize($local_file);
        //读取文件信息失败或者空文件
        if (empty($file_size))
        {
            return false;
        }
        $result = $this->request(array(
            'cmd' => 'upload',
            'size' => $file_size,
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

            //当前发送的数据长度
            $send_n = 0;
            while (!feof($fp))
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
                    //回调函数
                    if ($this->UploadCallback)
                    {
                        $send_n += strlen($read);
                        call_user_func($this->UploadCallback, $send_n, $file_size);
                    }
                }
                else
                {
                    echo "Error: read $local_file failed.\n";
                }
            }
            $ret = $this->sock->recv();
            if (!$ret)
            {
                return false;
            }
            $json = $this->unpack($ret);
            if (!$json or !isset($json['code']))
            {
                return false;
            }
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 创建文件或者目录
     * @param $path
     * @param bool $isdir
     * @return bool|mixed
     */
    function create($path, $isdir = false)
    {
        return $this->request([
            'cmd' => 'create',
            'path' => $path,
            'isdir' => $isdir,
        ]);
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
     * @param array $args
     * @return array
     */
    function execute($shell_script, $args = array())
    {
        return $this->request([
            'cmd' => 'execute',
            'shell_script' => $shell_script,
            'args' => $args,
        ]);
    }

    /**
     * @param $data
     * @param bool $json 是否进行JSON串化
     * @return bool|mixed
     */
    protected function request($data)
    {
        $pkg = $this->pack($data);
        $ret = $this->sock->send($pkg);
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
        $json = $this->unpack($ret);
        //服务器端返回的内容不正确
        if (!isset($json['code']))
        {
            $this->errCode = 9001;
            return false;
        }
        return $json;
    }
}