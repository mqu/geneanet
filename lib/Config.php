<?php


/*
 * simple configuration class management.

		require_once('lib/Config.php');

		// auto-load config file from : 
		//  - $HOME/.config/geneanet.ini,
		//  - current directory : geneanet.ini, config.ini, config.ini.default
		$config = new Config();
		var_dump($config->get('geneanet/default-url'));

 */

class Config{

	protected $cnf = null;

	public function __construct(){
		$this->auto_load();
		
		if($this->cnf == false){
			throw new Exception("Config::__construct() : no configuration file found.");
		}
		# print_r($this->cnf);
	}

	/* auto-loading (first found) :
	 *  $HOME/.config/geneanet.ini
	 *  ./geneanet.ini
	 *  ./config.ini
	 *  ./config.ini.default
	 */
	protected function auto_load(){
		
		$home_cnf = sprintf("%s/.config/geneanet.ini", getenv('HOME'));
		$files = array(
			$home_cnf,
			'geneanet.ini',
			'config.ini',
			'config.ini.default',
		);
		foreach($files as $file)
			if(file_exists($file)){
				$this->load($file);
				return true;
			}
		return false;
	}

	protected function load($file){
		if(!file_exists($file))
			throw new Exception (sprintf("Config::load(%s) : can't load file", $file));
		$this->cnf = parse_ini_file($file, true);
		if($this->cnf == false)
			throw new Exception (sprintf("Config::load(%s) : error reading file", $file));
	}

	public function __get($name){
		return $this->get($name);
	}

	public function get($name){
		if(isset($this->cnf[$name]))
			return $this->cnf[$name];
		elseif(preg_match('#(.*)/(.*)#', $name, $v)){
			if(isset($this->cnf[$v[1]][$v[2]]))
				return $this->cnf[$v[1]][$v[2]];
			else{
				error_log(sprintf("Config::get(%s) : warning : variable is not set\n", $name));
				return false;
			}
		}
		else{
			error_log(sprintf("Config::get(%s) : warning : variable is not set\n", $name));
			return false;
		}
	}
}

?>
