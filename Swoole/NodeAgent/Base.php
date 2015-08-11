<?php
namespace Swoole\NodeAgent;

use Swoole\DES;

require_once dirname(__DIR__) . '/DES.php';

class Base
{
    protected $encrypt;
    protected $des;

    /**
     * @param bool $encrypt
     * @param string $des_key
     * @throws \Exception
     */
    function __construct($des_key = '')
    {
        if (!empty($des_key))
        {
            if (empty($des_key))
            {
                throw new \Exception("require des key.");
            }
            $this->encrypt = true;
            $this->des = new DES($des_key);
        }
    }

    /**
     * 数据打包
     * @param $data
     * @param bool $encode_json
     * @return string
     */
    protected function pack($data, $encode_json = true)
    {
        //json编码
        if ($encode_json)
        {
            $_data_str = json_encode($data);
        }
        else
        {
            $_data_str = $data;
        }
        //加密
        if ($this->encrypt)
        {
            $_send_data = $this->des->encode($_data_str);
        }
        else
        {
            $_send_data = $_data_str;
        }
        //加入包头
        return pack('N', strlen($_send_data)) . $_send_data;
    }

    /**
     * 数据解包
     * @param $data
     * @param bool $decode_json
     * @return bool|mixed|string
     */
    protected function unpack($data, $decode_json = true)
    {
        $_data = substr($data, 4);
        if ($this->encrypt)
        {
            $_data = $this->des->decode($_data);
            if ($_data === false)
            {
                return false;
            }
        }
        if ($decode_json)
        {
            return json_decode($_data, true);
        }
        else
        {
            return $_data;
        }
    }
}