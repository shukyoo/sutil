<?php namespace Sutil\Curl;

class Fetcher
{
    const DEFAULT_TIMEOUT = 10;
    protected $_ch;

    public function __construct($options = [])
    {
        $this->_ch = curl_init();
        if (isset($options['timeout'])) {
            $this->setTimeout($options['timeout']);
        }
    }

    public function setTimeout($timeout)
    {
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, (int)$timeout);
    }

    public function get($url, $params = null)
    {
        if ($params) {
            $params = is_array($params) ? http_build_query($params) : ltrim($params, '&?');
            $url .= (strpos($url, '?') ? '&' : '?') . $params;
        }
        return $this->_fetch($url);
    }

    public function getJson($url, $params = null)
    {
        $content = $this->get($url, $params);
        if ($content) {
            $content = json_decode($content, true);
        }
        return $content;
    }

    public function post($url, $data)
    {
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($data));
        return $this->_fetch($url);
    }

    public function postJson($url, $data)
    {
        $content = $this->post($url, $data);
        if ($content) {
            $content = json_decode($content, true);
        }
        return $content;
    }

    public function getErrmsg()
    {
        return curl_error($this->_ch);
    }

    protected function _fetch($url)
    {
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        curl_setopt($this->_ch, CURLOPT_HEADER, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        if (strpos($url, 'ttps://')) {
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        return curl_exec($this->_ch);
    }

    public function __destruct()
    {
        curl_close($this->_ch);
    }
}