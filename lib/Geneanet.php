<?php


/* geany_encoding=ISO-8859-15 */

require_once('lib/CURL.php');



class GeneanetServer{

	protected $login;
	protected $passwd;

	protected $id;
	protected $idt;

	protected $curl; # handle IO with curl library


	public function __construct(){
		$this->curl = new Curl();
	}
	
	public function login($user, $passwd){

		$args = array(
			"login" 	 => $user,
			"password"	 => $passwd,
			"persistent" => 1,
			"sub"        => "Connexion"
		);
		$url = 'http://www.geneanet.org/';
		$data = $this->post($url, $args);
		
		# <p class="error">Erreur de connexion (identifiant/mot de passe). Merci de vérifier les informations saisies. </p>
		$expr = '#<p class="error">(.*)</p>#mi';
		if(preg_match($expr, $data, $values)){
			# $this->log("# connexion error");
			$this->error = $values[1];
			return false;
		}
		
		return true;
	}

	public function logout(){
		$args = array(
			"nologin" 	 => 1,
		);
		$url = 'http://www.geneanet.org/';
		$data = $this->post($url, $args);
	}
	
	
	public function fiche($url){
		
		$data = $this->get($url);
		print_r($data);
		
	}

	public function last_error(){
		return $this->error;
	}

	public function post($url, $args){
		return $this->curl->post($url, $args);
	}
	
	public function get($url){
		return $this->curl->get($url);
	}

	protected function log($msg){
		printf("%s\n", $msg);
	}
	
	public function set_proxy($proxy){
		$this->curl->set_proxy($proxy);
	}
}

?>
