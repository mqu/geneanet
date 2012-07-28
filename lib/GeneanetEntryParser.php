<?php

/*
 * 
 *  parse entry from Geneanet genealogy server
 * 
 * 
 */

/* geany_encoding=ISO-8859-15 */

require_once('lib/simplehtmldom.php');
require_once('lib/Person.php');

define("SECTION_INFO",           "info");
define("SECTION_HALF_SIBLINGS",  "half-siblings");
define("SECTION_SIBLINGS",       "siblings");
define("SECTION_PARENTS",        "parents");
define("SECTION_UNIONS",         "unions");
define("SECTION_NOTES",          "notes");
define("SECTION_SOURCES",        "sources");
define("SECTION_RELATIONSHIPS",  "relationships");
define("SECTION_FAMILY_NOTES",   "family-notes");


class GeneanetEntryParser{

	public function __construct(){
		
	}
	
	public function parse($data_html, $url){

		$person = new Person();
		$html = new simple_html_dom($data_html);

		# incorect request
		if($h1 = $html->find('h1', 1)) {
			# printf("# ERR : incorrect request : '%s'\n", $h1->plaintext);
			if($h1->plaintext == 'Incorrect request')
				return false;
		}

		# non "mobile version" page
		if(!preg_match('#This is the Mobile version of the GeneaNet web site. Click here to go to the regular version.#im', $data_html)) {
			error_log("# ERR : html page not in mobile version ($url)\n");
			return false;
		}
		$div = $html->find('div#tree_content h2,ul,p,table');

		$section = SECTION_INFO;
		foreach($div as $e){
			
			# printf("\n# section : %s / [%s] %s\n", $section, $e->tag, $this->encode($this->str_limit($e->plaintext)));

			if($e->tag == 'h2'){
				# printf("# %s / H2 : %s\n", $section, $e->plaintext);
				
				# Half-siblings
				if(preg_match('/Half-siblings/i', $e->plaintext)){
					# printf("# match half-sibling\n");
					$section = SECTION_HALF_SIBLINGS;
				}

				# Siblings
				elseif(preg_match('/Siblings/i', $e->plaintext)){
					# printf("# match sibling\n");
					$section = SECTION_SIBLINGS;
				}
				
				# Parents
				elseif(preg_match('/Parents/i', $e->plaintext)){
					# printf("# match Parents\n");
					$section = SECTION_PARENTS;
				}
				
				# Spouses and children, 
				elseif(preg_match('/Spouses( and children|)/i', $e->plaintext)){
					# printf("# match unions\n");
					$section = SECTION_UNIONS;
				}

				# notes
				elseif(preg_match('/Notes/i', $e->plaintext)){
					# printf("# match notes\n");
					$section = SECTION_NOTES;
				}
				
				#elseif(preg_match('/Famil*y.*note/i', $e->plaintext)){
				# 	printf("# match Family notes\n");
				# 	$section = SECTION_FAMILY_NOTES;
				# }
		
				elseif(preg_match('/Sources/i', $e->plaintext)){
					# printf("# match sources\n");
					$section = SECTION_SOURCES;
				}
				# elseif(preg_match('/Relationships/i', $e->plaintext)){
				#	# printf("# match sources\n");
				#	$section = SECTION_RELATIONSHIPS;
				#}
				
				# default section
				elseif($section == 'info')
					$person = $this->parse_info($person, $e);

				# elseif($section != 'info') {
				else {
					# TODO : Family Tree Preview
					error_log(sprintf("# TODO : GeneanetEntryParser::parse(%s) : section non geree : [%s]:'%s' url=%s", 
						utf8_decode($person->name()), $e->tag, $e->plaintext, $url));
				}
			
				# printf("# nouvelle section : %s / H2 : %s\n", $section, $e->plaintext);
				
			} else {
				
				switch($section){
				case SECTION_INFO:
					$person = $this->parse_info($person, $e);
					break;
				case SECTION_SIBLINGS:
					if($e->tag == 'p')
						break;

					$person = $this->parse_siblings($person, $e);
					break;
				case SECTION_HALF_SIBLINGS:
					if($e->tag == 'p')
						break;
					$person = $this->parse_half_siblings($person, $e);
					break;
				case SECTION_PARENTS:
					if($e->tag == 'p')
						break;
					$person = $this->parse_parents($person, $e);
					break;
				case SECTION_UNIONS:
					if($e->tag == 'p')
						break;
					if(preg_match('/Regular Version/', $e->innertext))
						break;
					if(@count($person->unions) == 0){
						# printf("##  unions : [%s] : %s \n", $e->tag, $e->innertext);
						$person = $this->parse_unions($person, $e);
					}
					break;
				case SECTION_NOTES:
					$person = $this->parse_notes($person, $e);
					break;
				case SECTION_FAMILY_NOTES:
					$person = $this->parse_family_notes($person, $e);
					break;
				case SECTION_SOURCES:
					$person = $this->parse_sources($person, $e);
					break;
				default:
					throw new Exception ("you know what ??");
					break;
				}
			}
		}
		
		return $person;
	} /* parse (html) */


	function parse_unions($person, $html){

		# printf("# parse_unions(%s) : %s\n", $person->name(), $html->plaintext);
		# printf("code : %s\n", $html->innertext);
		
		/* content :
		 * ul
		 *   li : union [parents]
		 *   ul
		 *    li : <img gender> <a href="">child1</a>
		 *    li : <img gender> <a href="">child2</a> with ...
		 *   /ul
		 * /ul
		 * 
		 * plaintext :
		 *  - Married [date], [place], to [person] 1967 (Parents : )  with   
		 *    - [person] [date]
		 *    - [person] [date]
		 * 
		 *  exception :
		 * 
		 *   Married to x x with 
		 *    - x x
		 */
		 
		 // TODO : get extra information for unions (date and place).
		
		foreach($html->find('li') as $li){

			
			if(!preg_match('#.*li/ul/li$#', $this->node_path($li, false))){

				$union = array(
					'name'   => NULL,
					'url'    => NULL
				);
				if($a = $li->find('a', 0)){
					$union['name'] = $this->encode($this->replace_nbsp($a->plaintext));
					$union['url'] = $a->href;
				}

				# childs
				foreach($li->find("ul li") as $li){
					if(!$a = $li->find('a', 0))
						continue;
					$img = $li->find('img', 0);
					if($img == false)
						$gender = NULL;
					else
						$gender = strtoupper($img->alt);
					$union['childs'][] = array(
						'name'    => trim($this->encode($this->replace_nbsp($a->plaintext))),
						'url'     => $a->href,
						'gender'  => $gender
					 );
				}
				
				$person->push('unions', $union);
			}
		}
		
		return $person;
	}

	function parse_notes($person, $html){
		if(preg_match('/(Regular Version)|(The Online Family Trees)/', $html->innertext))
			return $person;
		if(preg_match('/(Relationship.*Descendant)|(Relationship.*Ancestry)/', $html->plaintext))
			return $person;
		if(strlen(trim($html->plaintext)) == 0)
			return $person;

		$person->push('notes', $this->encode($this->replace_nbsp($html->plaintext)));

		return $person;
	}


	function parse_family_notes($person, $html){
		printf("# family_notes (%s)\n", $html->tag);
		if(strlen(trim($html->plaintext)) == 0)
			return $person;
		error_log("# TODO : need to complete parse_family_notes() method");
		$person->push('familly-notes', $this->encode($html->plaintext));
		return $person;
	}

	function parse_sources($person, $html){

		# only take care of '<ul>' components
		if($html->tag != 'ul')
			return $person;

		if(preg_match('/(Regular Version)|(The Online Family Trees)/', $html->innertext))
			return $person;

		foreach($html->find("li em") as $t){
			$person->push('sources', $this->encode($t->plaintext));
		}
		return $person;
	}

	function parse_siblings($person, $html){
		# printf("# parse_siblings() : [%s]\n", $html->tag);

		$i = 0;
		while($a = $html->find('a', $i)){
			if($a->plaintext == 'Regular Version')
				return $person;
			$person->push('siblings', array(
			  'name' => $this->encode($a->plaintext),
			  'url'  => $a->href));
			$i++;
		}

		return $person;
	}

	function parse_half_siblings($person, $html){

		if($html->tag != 'table')
			return $person;

		# printf("# parse_half_siblings() : [%s] / %s : %s\n", $html->tag, $this->node_path($html), $html->plaintext);

		$half = array();
		
		$i = 0;
		while($a = $html->find('a', $i)){
			
			# printf("# half-siblings  $i : %s (%s)\n", $a->plaintext, $a->href);

			switch ($i){
			case 0:
			case 1:
				$half['parents'][] = array(
					'name' => $this->encode($a->plaintext),
					'url'  => $a->href
				);
				break;

			default:
				$half['siblings'][] = array(
					'name' => $this->encode($a->plaintext),
					'url'  => $a->href
				);
				break;
			}
			
			$i++;
		}

		$person->push('half_siblings', $half);

		return $person;
	}

	function parse_parents($person, $html){
		$i = 0;
		while($a = $html->find('a', $i)){

			$person->push('parents', array(
			  'name' => $this->encode($a->plaintext),
			  'url'  => $a->href));
			$i++;
		}

		return $person;
	}

	function parse_info($person, $html){

		# printf("# parse_info() : [%s]\n", $html->tag);

		# <img src="http://static.geneanet.org/arbre/img/man.png" alt="H" title="H" />&nbsp; 
		# <a href="mq31?lang=fr;pz=marc;nz=quinton;ocz=0;templ=mobile;m=P;v=jean+marie+louis+pierre;o=i">Jean Marie Louis Pierre</a>
		# <a href="mq31?lang=fr;pz=marc;nz=quinton;ocz=0;templ=mobile;m=N;v=quinton;o=i">QUINTON</a>
		if($html->tag == 'h2'){
			
			# gender (sex)
			if($img = $html->find('img', 0)){
				$person->gender = strtoupper($img->title);
			}
			
			# firstname
			if($img = $html->find('a', 0)){
				$person->first = trim($this->encode($img->plaintext));
			}
			# lastname
			if($img = $html->find('a', 1)){
				$person->last = trim($this->encode($img->plaintext));
			}

		} else {

			$list = $html->find('li');
			
			foreach($list as $e){
				$txt = trim(html_entity_decode($this->replace_nbsp($e->plaintext)));

				# Born 8 August 1960 - Saint-Brieuc,22000,France
				if(preg_match('/^Born (.*?) - (.*)/i', $txt, $values)){
					$person->birth = array(
							'date'  => strtolower(trim($this->encode($values[1]))),
							'place' => trim($this->encode($values[2]))
						);
				}
				
				# Born - [place]
				elseif(preg_match('/^Born\s+-\s*(.*)/i', $txt, $values)){
					$person->birth = array(
							'date'  => NULL,
							'place' => trim($this->encode($values[1]))
						);
				}

				# Born 14 October 1901
				elseif(preg_match('/^Born (.*)/i', $txt, $values)){
					$person->birth = array(
							'date'  => strtolower(trim($this->encode($values[1]))),
							'place' => NULL
						);
				}

				# Died - [place]
				elseif(preg_match('/^Died\s+-\s*(.*)/i', $txt, $values)){
					$person->birth = array(
							'date'  => NULL,
							'place' => trim($this->encode($values[1]))
						);
				}

				# Died [date] - [place], at ...
				else if(preg_match('/^Died (.*?) - (.*?) , age at/i', $txt, $values)){
					$person->death = array(
							'date'  => strtolower(trim($this->encode($values[1]))),
							'place' => trim($this->encode($values[2]))
						);
				}

				# Died [date] - [place]
				else if(preg_match('/^Died (.*?) - (.*?)/i', $txt, $values)){
					$person->death = array(
							'date'  => strtolower(trim($this->encode($values[1]))),
							'place' => trim($this->encode($values[2]))
						);
				}
				
				# Died [date] , age at death: 44 years old
				else if(preg_match('/^Died (.*?) , age at/i', $txt, $values)){
					$person->death = array(
							'date'  => strtolower(trim($this->encode($values[1]))),
							'place' => NULL
						);
				}
				
				# Died 
				else if(preg_match('/^Died\s*$/i', $txt, $values)){
					$person->death = array(
							'status' => 'died',
							'date'  => NULL,
							'place' => NULL
						);
				}
			
				else if(preg_match('/^Age : (.*) years old/i', $txt, $values)){
					$person->age = trim($values[1]);
				}
				else if(preg_match('/^Age : (.*) /i', $txt, $values)){
					$person->age = trim($values[1]);
				}
			
				# Baptized 28 August 1960 - Saint-Brieuc,22000,France
				else if(preg_match('/^Baptized (.*?) - (.*)/i', $txt, $values)){
					$person->baptism = array(
							'date'  => strtolower(trim($this->encode($values[1]))),
							'place' => trim($values[2])
						);
				}

				# Baptized 28 August 1960
				else if(preg_match('/^Baptized (.*?)/i', $txt, $values)){
					$person->baptism = array(
							'date'  => strtolower(trim($this->encode($values[1]))),
							'place' => NULL
						);
				} 
				# Consanguinity : 0.13%
				else if(preg_match('/^Consanguinity : (.*%)/i', $txt, $values)){
					$person->consanguinity = $values[1];
				} 
				
				else {
					$person->push('jobs',trim($this->encode($txt)));
				}
			}
		}
		return $person;
	}

	function replace_nbsp($str){
		$from = array('&nbsp;', '&amp;');
		$to = array(' ', '&');
		return str_replace($from, $to, $str);
	}

	function encode($str){
		return ($str);
		# return utf8_decode($str);
	}

	function str_proper($str){
		$str = str_replace('&nbsp;', ' ', $str);
		return trim(strtr(html_entity_decode($str), "\n", ' '));
	}

	function str_reformat($str){
		return preg_replace("#\n#", '\n', $str);
	}

	function str_limit($str, $len=70){
		if(strlen($str) <= $len)
			return $str;
		return sprintf("%s ...", substr($str, 0, $len));
	}

	function node_path($html, $full=true){
		$list = array();
		
		$p = $html;
		while($p->tag != 'root'){
			if($full && isset($p->class))
				$list[] = sprintf("%s.%s", $p->tag, $p->class);
			elseif($full && isset($p->id))
				$list[] = sprintf("%s#%s", $p->tag, $p->id);
			else
				$list[] = $p->tag;

			$p = $p->parent;
		}

		return '/' . join('/', array_reverse($list));
	}
}
?>
