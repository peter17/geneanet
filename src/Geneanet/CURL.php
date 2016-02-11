<?php

namespace Geneanet;

/* geany_encoding=ISO-8859-15 */

class CURL
{
    protected $user_agent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0';
    protected $proxy = null;

    public function __construct()
    {
        
        # this is default with CURL : reads http_proxy
        if (getenv('http_proxy')) {
            $this->setProxy(getenv('http_proxy'));
        }
    }
    
    public function doRequest($method, $url, $vars)
    {
        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_URL, $url);
        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, true);

        if ($this->proxy != null) {
            curl_setopt($curlHandler, CURLOPT_PROXY, $this->proxy);
        }

        # curl_setopt($curlHandler, CURLOPT_VERBOSE, true); verbose mode
        if (!$this->isSafeMode()) {
            curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curlHandler, CURLOPT_COOKIEJAR, 'var/cookie.txt');
            curl_setopt($curlHandler, CURLOPT_COOKIEFILE, 'var/cookie.txt');
        }
    
        if ($method == 'POST') {
            curl_setopt($curlHandler, CURLOPT_POST, 1);
            curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $vars);
        }
        $data = curl_exec($curlHandler);
        curl_close($curlHandler);
        if ($data) {
            return $data;
        } else {
            return curl_error($curlHandler);
        }
    }

    public function get($url)
    {
        return $this->doRequest('GET', $url, 'NULL');
    }
    
    public function post($url, $vars)
    {
        return $this->doRequest('POST', $url, $vars);
    }
    
    protected function isSafeMode()
    {
        if (ini_get('safe_mode')===true) {
            return true;
        }
        if (ini_get('open_basedir') !== '') {
            return true;
        }
        return false;
    }
    
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }
}
