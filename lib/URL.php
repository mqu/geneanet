<?php

/*

  URL class wrapper : manipulate URL parts.

  simple test case usage :

	require_once('lib/URL.php');

	$url = new URL();
	$_url = 'http://www.google.fr/search?content=genealogy';

	$args = $url->split(rawurldecode($_url));
	$arg->lang = 'fr';
	unset($args->lang);
	echo $args->build() . "\n";

	will output : "http://www.google.fr/search?content=genealogy&lang=fr"

*/

/* geany_encoding=ISO-8859-15 */

class URL {

	public function __construct(){

	}

	# see also : http://www.php.net/manual/en/function.parse-url.php
	public function split($url){
		if(preg_match('#(https*://.*//*)(.*?)\?(.*)#', $url, $values)){
			$_url = array();
			$_url['url'] = array_shift($values);
			$_url['base'] = array_shift($values);
			$_url['cmd'] = array_shift($values);
			$_url['args']['string'] = $values[0];
			
			# some site (geneanet) take args separated with both '&' and ';'
			# $values = explode("&", $values[0]);
			$values = preg_split('/(;|&)/', $values[0]);
			
			foreach($values as $k=>$v){
				$vv = explode("=", $v);
				$_url['args']['values'][$vv[0]] = $vv[1];
			}

			return new UrlArgs($_url);
		}

		throw new Exception ("wrong url format '$url'\n");
	}

	public function base($url){
		return $this->split($url)->base;
		throw new Exception ("wrong url format '$url'\n");
	}

	public function check($url){
		if(!preg_match('#https*://(.*)/(.*)#', $url))
			return false;
		return true;
	}
	
	public function enforce_params($_url, $params){
		$args = $this->split($_url);
		foreach($params as $k=>$v)
			$args->$k=$v;
		return $args->build();
	}
}

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
class UrlArgs{
	protected $args;

	public function __construct($args){
		$this->args = $args;
	}
	public function __get($name){
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

	public function get($name){
		switch($name){
			case 'base':
			case 'cmd':
			case 'args':
				return $this->args[$name];
	
			case 'values':
				return $this->args['args']['values'];

			default:
				if(preg_match("#args/(.*)#", $name, $v)){
					return $this->args['args']['values'][$v[1]];
				}
		}
	}

	public function __set($name, $value){
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

	public function __unset($name){
		return $this->_unset($name);
	}

	
	public function set($name, $value){
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
				if(preg_match("#args/(.*)#", $name, $v)){
					$this->args['args']['values'][$v[1]] = $value;
				}
		}
	}

	public function _unset($name){
		if(isset($this->args['args']['values'][$name]))
			unset($this->args['args']['values'][$name]);
		else{
			error_log("URL:unset($name) error");
			return false;
		}
	}

	public function __isset($name){
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
	public function build(){
		foreach($this->values as $k=>$v)
			$args[] = sprintf("%s=%s", $k, urlencode($v));
		$args = join('&', $args);
		return sprintf('%s%s?%s', $this->base, $this->cmd, $args);
	}
}

?>
