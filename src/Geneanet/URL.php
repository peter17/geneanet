<?php

namespace Geneanet;

/*

  URL class wrapper : manipulate URL parts.

  simple test case usage :


	$url = new URL();
	$_url = 'http://www.google.fr/search?content=genealogy';

	$args = $url->split(rawurldecode($_url));
	$arg->lang = 'fr';
	unset($args->lang);
	echo $args->build() . "\n";

	will output : "http://www.google.fr/search?content=genealogy&lang=fr"

*/

/* geany_encoding=ISO-8859-15 */

class URL
{

    public function __construct()
    {

    }

    # see also : http://www.php.net/manual/en/function.parse-url.php
    public function split($url)
    {
        if (preg_match('#(https*://.*//*)(.*?)\?(.*)#', $url, $values)) {
            $_url = array();
            $_url['url'] = array_shift($values);
            $_url['base'] = array_shift($values);
            $_url['cmd'] = array_shift($values);
            $_url['args']['string'] = $values[0];
            
            # some site (geneanet) take args separated with both '&' and ';'
            # $values = explode("&", $values[0]);
            $values = preg_split('/(;|&)/', $values[0]);
            
            foreach ($values as $v) {
                $vv = explode("=", $v);
                $_url['args']['values'][$vv[0]] = $vv[1];
            }

            return new URLArgs($_url);
        }

        throw new Exception("wrong url format '$url'\n");
    }

    public function base($url)
    {
        return $this->split($url)->base;
        throw new Exception("wrong url format '$url'\n");
    }

    public function check($url)
    {
        if (!preg_match('#https*://(.*)/(.*)#', $url)) {
            return false;
        }
        return true;
    }
    
    public function enforceParams($_url, $params)
    {
        $args = $this->split($_url);
        foreach ($params as $k => $v) {
            $args->$k=$v;
        }
        return $args->build();
    }
}
