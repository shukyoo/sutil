<?php namespace Sutil\Encryption;


class TDES
{
    protected static $_key = '';
    protected static $_iv = '';

    public static function config(array $config)
    {
        self::$_key = empty($config['key']) ? '' : $config['key'];
        self::$_iv = empty($config['iv']) ? '' : $config['iv'];
    }

    /**
     * Encrypt
     * @param string $input
     * @return string
     */
    public static function encrypt($input)
    {
        $srcdata = $input;
        $block_size = mcrypt_get_block_size('tripledes', 'ecb');
        $padding_char = $block_size - (strlen($input) % $block_size);
        $srcdata .= str_repeat(chr($padding_char), $padding_char);
        return Base32::encode(mcrypt_encrypt(MCRYPT_3DES, self::_key(), $srcdata, MCRYPT_MODE_CBC, self::_iv()));
    }

    /**
     * Decrypt
     * @param string $input
     * @return string
     */
    public static function decrypt($input)
    {
        $result = mcrypt_decrypt(MCRYPT_3DES, self::_key(), Base32::decode($input), MCRYPT_MODE_CBC, self::_iv());
        $end = ord(substr($result, - 1));
        return substr($result, 0, - $end);
    }


    protected static function _key()
    {
        if (strlen(self::$_key) != 48) {
            throw new \Exception('Invalid key for encrypt');
        }
        return pack('H48', self::$_key);
    }

    protected static function _iv()
    {
        if (strlen(self::$_iv) != 16) {
            throw new \Exception('Invalid iv for encrypt');
        }
        return pack('H16', self::$_iv);
    }

}