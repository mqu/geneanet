<?php

require_once('lib/URL.php');
require_once('lib/Person.php');
require_once('lib/Geneanet.php');
require_once('lib/GeneanetEntryParser.php');

/* geany_encoding=ISO-8859-15 */

class Grabber {
	
	protected $geneanet;
	protected $delay = 1;
	
	// will force this args in request (url).
	protected $geneanet_args = array(
		'lang' => 'en',
		'templ' => 'mobile'
	);

	public function __construct($geneanet){
		$this->geneanet = $geneanet;
		$this->parser = new GeneanetEntryParser();
		$this->url = new Url();
	}

	function grab_single($url){

		$url = $this->url->enforce_params($url, $this->geneanet_args);

		$html = $this->url_grab_cached($url);
		
		if($html == false){
			return false;
		}

		$person = $this->parse($html, $url);
		$person->url = $url;

		return $person;
	}

	function grab_descendants($p, $level=3, $indent=0){
		
		# printf("# grab_descendants(%s, %s)\n", $level, utf8_decode($p->name()));

		if($level == 0)
			return;

		$unions = $this->grab_unions($p);
		$p->set('unions', $unions);

		foreach($unions as $union){
			foreach($union['childs'] as $c){
				# printf("  - %s\n", utf8_decode($c->name()));
				#printf("# %s - %s\n", 
				#	str_repeat("   - ", $indent), 
				#	utf8_decode($c->quick_display())	
				#	);
				$this->grab_descendants($c, $level-1, $indent+1);
			}			
		}
	}

	function grab_ascendants($p, $level=3){
		
		# printf("# grab_parents(%s) %s\n", $level, $->quick_display());

		if($level == 0)
			return;

		$parents = $p->parents;
		$_parents = array();
		
		if(isset($parents[0]['url'])){
			$url = $this->make_url($p->url, $parents[0]);
			$p1 = $this->grab_single($url);
			$_parents[0] = $p1;
			$this->grab_ascendants($p1, $level-1);
		}
		if(isset($parents[1]['url'])) {
			$url = $this->make_url($p->url, $parents[1]);
			$p1 = $this->grab_single($url);
			$_parents[1] = $p1;
			$this->grab_ascendants($p1, $level-1);
		}
		printf(" %s - (%d) %s\n", 
			str_repeat("   ", $level),
			$level,
			utf8_decode($p->quick_display())
			);

		$p->set('parents', $_parents);

	}
	
	function grab_unions($p){
		return $this->grab_unions_and_childs($p);
	}

	function grab_unions_and_childs($p){
		
		# printf("# grab_unions_and_childs(%s) / %s\n", utf8_decode($p->name()), $p->url);

		$list = array();
		$unions = $p->unions;
		$_unions = array();

		foreach($unions as $u){
			$_unions = array();
			if($u['url'] == null)
				continue;
			$url = $this->make_url($p->url, $u);
			$_union['self'] = $p;
			$_union['spouse'] = $this->grab_single($url);
			$_union['childs'] = array();
			
			if(isset($u['childs'])){
				foreach($u['childs'] as $c){
					$url = $this->make_url($p->url, $c);
					$_union['childs'][] = $this->grab_single($url);
				}
			}
			
			$_unions[] = $_union;
		}
		
		return $_unions;
	}

	function grab_siblings($p){
		
		# printf("# grab_siblings(%s) / %s\n", utf8_decode($p->name()), $p->url);

		$list = array();
		$siblings = $p->siblings;

		foreach($siblings as $s){
			if($s['url'] == null)
				continue;
			$url = $this->make_url($p->url, $s);
			$list[] = $this->grab_single($url);
		}
		
		return $list;
	}

	function grab_half_siblings($p){
		
		# printf("# grab_half_siblings(%s) / %s\n", utf8_decode($p->name()), $p->url);

		$list = array();
		$siblings = $p->half_siblings;

		foreach($siblings as $s){
			if($s['url'] == null)
				continue;
			$url = $this->make_url($p->url, $s);
			$list[] = $this->grab_single($url);
		}
		
		return $list;
	}

	protected function make_url($url, $p){
		return sprintf('%s%s', $this->url->base($url), $p['url']);
	}

	protected function url_grab_cached($url){

		$md5 = md5($url);
		$cache = "var/cache/" . $md5 . '.html';

		if(file_exists($cache))
			return file_get_contents($cache);

		$html = $this->url_grab($url);

		file_put_contents("var/cache/" . $md5 . '.html', $html);

		return $html;
	}

	protected function url_grab($url){

		# printf("# grab ($url) : %s\n", $url);
		$count=0;
		while(true){
			$html = $this->geneanet->get($url);
			$this->delay();
			if($html !== false){
				if(! $this->check_incorrect($html))
					return $html;

				echo "# incorrect request : $url\n";

				$count++;
				if($count > 10){
					echo "# giving up for : $url";
					return false;
				}
				sleep(5);

			}
			sleep(5);
		}

		return $html;
	}

	protected function check_incorrect($html){
		if(preg_match('#<h1>Incorrect request</h1>#i', $html))
			return true;
		return false;
	}

	protected function parse($html, $url){
		return $this->parser->parse($html, $url);
	}

	public function set_proxy($proxy){
		$this->geneanet->set_proxy($proxy);
	}
	
	protected function delay($sec=null){
		if($sec == null)
			$sec = $this->delay;
		sleep($sec);
	}
	
	/* won't go under 1 second between each request */
	public function set_delay($sec){
		if($sec<1)
			$sec = 1;
		$this->delay = $sec;
	}
}


?>
