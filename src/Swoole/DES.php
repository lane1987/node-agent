<?php
namespace Swoole;

/**
 * 对称加密算法类
 * 支持密钥：64/128/256 bit（字节长度8/16/32）
 * 支持算法：DES/AES（根据密钥长度自动匹配使用：DES:64bit AES:128/256bit）
 * 支持模式：CBC/ECB/OFB/CFB
 * 密文编码：base64字符串/十六进制字符串/二进制字符串流
 * 填充方式: PKCS5Padding（DES）
 */
class DES
{
    private $mcrypt;
    private $key;
    private $mode;
    private $iv;
    private $blocksize;

    /**
     * 构造函数
     * @param string $key 密钥
     * @param string $mode 模式
     * @throws \Exception
     */
    public function __construct($key, $mode = 'cbc')
    {
        if (!function_exists('mcrypt_create_iv'))
        {
            throw new \Exception(__CLASS__ . " require mcrypt extension.");
        }

        switch (strlen($key))
        {
            case 8:
                $this->mcrypt = MCRYPT_DES;
                break;
            case 16:
                $this->mcrypt = MCRYPT_RIJNDAEL_128;
                break;
            case 32:
                $this->mcrypt = MCRYPT_RIJNDAEL_256;
                break;
            default:
                throw new \Exception("des key size must be 8/16/32");
        }

        $this->key = $key;

        switch (strtolower($mode))
        {
            case 'ofb':
                $this->mode = MCRYPT_MODE_OFB;
                break;
            case 'cfb':
                $this->mode = MCRYPT_MODE_CFB;
                break;
            case 'ecb':
                $this->mode = MCRYPT_MODE_ECB;
                break;
            case 'cbc':
            default:
                $this->mode = MCRYPT_MODE_CBC;
        }
    }

    function getIV()
    {
        $source = PHP_OS == 'WINNT' ? MCRYPT_RAND : MCRYPT_DEV_RANDOM;
        $this->iv = mcrypt_create_iv(mcrypt_get_block_size($this->mcrypt, $this->mode), $source);
    }

    function setIV($iv)
    {
        $this->iv = $iv;
    }

    /**
     * 加密
     * @param string $str 明文
     * @return string 密文
     */
    public function encode($str)
    {
        if ($this->mcrypt == MCRYPT_DES)
        {
            $str = $this->_pkcs5Pad($str);
        }
        return mcrypt_encrypt($this->mcrypt, $this->key, base64_encode($str), $this->mode, $this->iv);
    }

    /**
     * 解密
     * @param string $str 密文
     * @return string 明文
     */
    public function decode($str)
    {
        $ret = mcrypt_decrypt($this->mcrypt, $this->key, $str, $this->mode, $this->iv);
        if ($this->mcrypt == MCRYPT_DES)
        {
            $ret = $this->_pkcs5Unpad($ret);
        }
        return base64_decode(rtrim($ret));
    }

    private function _pkcs5Pad($text)
    {
        $this->blocksize = mcrypt_get_block_size($this->mcrypt, $this->mode);
        $pad = $this->blocksize - (strlen($text) % $this->blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    private function _pkcs5Unpad($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
        {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
        {
            return false;
        }
        $ret = substr($text, 0, -1 * $pad);
        return $ret;
    }
}