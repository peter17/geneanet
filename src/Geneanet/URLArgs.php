<?php

namespace Geneanet;

/*

	Array
	(
		[url] => http://www.site.org/cmd?args....
		[base] => http://www.site.org/
		[cmd] => cmd
		[args] => Array
			(
				[string] => args1=1&args2=xx...
				[values] => Array
					(
						[arg1] => 1
						[arg2] => xx
					)
			)
	)

*/
class URLArgs
{
    protected $args;

    public function __construct($args)
    {
        $this->args = $args;
    }
    public function __get($name)
    {
        switch($name){

            case 'args':
                return $this->args;

            case 'base':
            case 'cmd':
            case 'values':
                return $this->get($name);

            default:
                return($this->get('args/' . $name));
        }
    }

    public function get($name)
    {
        switch($name){
            case 'base':
            case 'cmd':
            case 'args':
                return $this->args[$name];
    
            case 'values':
                return $this->args['args']['values'];

            default:
                if (preg_match("#args/(.*)#", $name, $value)) {
                    return $this->args['args']['values'][$value[1]];
                }
        }
    }

    public function __set($name, $value)
    {
        switch($name){

            case 'args':
            case 'base':
            case 'cmd':
            case 'values':
                $this->set($name, $value);
                break;

            default:
                $this->set('args/' . $name, $value);
        }
    }

    public function __unset($name)
    {
        return $this->_unset($name);
    }

    
    public function set($name, $value)
    {
        switch($name){
            case 'base':
            case 'cmd':
            case 'args':
                $this->args[$name] = $value;
                break;
            case 'values':
                $this->args['args']['values'] = $value;
                break;

            default:
                if (preg_match("#args/(.*)#", $name, $match)) {
                    $this->args['args']['values'][$match[1]] = $value;
                }
        }
    }

    public function _unset($name)
    {
        if (isset($this->args['args']['values'][$name])) {
            unset($this->args['args']['values'][$name]);
        } else {
            error_log("URL:unset($name) error");
            return false;
        }
    }

    public function __isset($name)
    {
        switch($name){
            case 'base':
            case 'cmd':
            case 'args':
                return isset($this->args[$name]);

            case 'values':
                return isset($this->args['args']['values']);

            default:
                return isset($this->args['args']['values'][$name]);
        }
    }

    # see : http://www.php.net/manual/en/function.parse-url.php
    public function build()
    {
        foreach ($this->values as $k => $v) {
            $args[] = sprintf("%s=%s", $k, urlencode($v));
        }
        $args = join('&', $args);
        return sprintf('%s%s?%s', $this->base, $this->cmd, $args);
    }
}
