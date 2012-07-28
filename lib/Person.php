<?php

/*
 * handle data for a Person
 * 
 */

/* geany_encoding=ISO-8859-15 */


class Person {
	
	protected $args;
	protected $has_changed;

	public function __construct(){
		$this->args = array(
			'gender' => NULL,
			'first'  => NULL,
			'last'   => NULL,

			'birth' => array(
				'date' => NULL,
				'place' => NULL
			),
			'death' => array(
				'date' => NULL,
				'place' => NULL
			),

			'parents' => array(
				# array(
				#	'name' => NULL,
				#	'url' => NULL
				#),
				#array(
				#	'name' => NULL,
				#	'url' => NULL
				#)
				),
			'unions' => array(),
			'siblings' => array(),
			'half_siblings' => array(),

			'jobs'   => array(),
			'notes'  => array()
		);	
	}

	
	public function __get($name){
		if(isset($this->args[$name]))
			return $this->args[$name];
		return null;
	}

	public function __isset($name){
		return isset($this->args[$name]);
	}
	
	public function __set($name, $value){
		$this->args[$name] = $value;
		$this->has_changed = true;
	}

	public function __unset($name){
		unset($this->args[$name]);
		$this->has_changed = true;
	}

	public function name(){
		return $this->first . " " . $this->last;
	}

	public function quick_display(){
		return sprintf("[%s-%s] %s [%s] / %s",
			$this->year($this->birth['date']),
			$this->year($this->death['date']),
			$this->name(), 
			$this->birth['date'],
			$this->birth['place']
		);
	}

	public function &get($key){
		return $this->args[$key];
	}

	public function set($key, $value){
		$this->args[$key] = $value;
	}

	public function push($key, $value){
		$this->args[$key][] = $value;
	}
	public function parent_add($value){
		$this->push('parents', $value);
	}

	public function sibling_add($value){
		$this->push('sibling', $value);
	}

	public function half_sibling_add($value){
		$this->push('half_sibling', $value);
	}

	public function job_add($value){
		$this->push('jobs', $value);
	}

	public function union_add($value){
		$this->push('unions', $value);
	}

	public function __toString(){
		$txt =  sprintf("> %s : %s %s\n", $this->gender, $this->first, $this->last);
		

		if(isset($this->birth)){
			$txt .= sprintf(" - naissance : %s / %s\n", $this->birth['date'], $this->birth['place']);
		}

		if(isset($this->death)){
			$txt .= sprintf(" - décès : %s / %s\n", $this->death['date'], $this->death['place']);
		}

		if(isset($this->parents)){
			$txt .= sprintf("\n - Parents :\n");
			foreach($this->parents as $p)
				$txt .= sprintf("  * %s\n", $p['name']);
		}

		if(isset($this->unions)) foreach($this->unions as $u){
			$txt .= sprintf("\n - Mariage : %s\n", $u['name']);
			if(isset($u['childs']) && count($u['childs']) > 0){
				foreach($u['childs'] as $c){
					$txt .= sprintf("  * %s - %s\n", $c['gender'], $c['name']);
				}
			}
		}
	
		if(isset($this->sibling)){
			$txt .= sprintf("\n - Fratrie :\n");
			foreach($this->sibling as $s)
				$txt .= sprintf("  * %s\n", $s['name']);
		}

		if(isset($this->half_siblings)){
			$txt .= sprintf("\n - Demi-fratrie :\n");
			foreach($this->half_siblings as $rec){
				$txt .= sprintf("  * %s + %s :\n", 
						$rec['parents'][0]['name'],
						$rec['parents'][1]['name']
					);
				
				foreach($rec['siblings'] as $p)
					$txt .= sprintf("    - %s\n", $p['name']);

			}
		}

		if(isset($this->notes)){
			$txt .= sprintf("\n - Notes :\n");
			foreach($this->notes as $n)
				$txt .= sprintf("  * %s\n", $n);
		}

		if(isset($this->sources)){
			$txt .= sprintf("\n - Sources :\n");
			foreach($this->sources as $n)
				$txt .= sprintf("  * %s\n", $n);
		}

		return $txt;
	}

	public function to_html(){
		$txt =  sprintf("> %s : <b>%s %s</b><br>\n", $this->gender, $this->first, $this->last);

		if(isset($this->birth)){
			$txt .= sprintf("<li>naissance : %s / %s</li>\n", $this->birth['date'], $this->birth['place']);
		}

		if(isset($this->death)){
			$txt .= sprintf("<li>décès : %s / %s</li>\n", $this->death['date'], $this->death['place']);
		}

		if(isset($this->parents)){
			$txt .= sprintf("<li>Parents :</li>\n<ul>");
			foreach($this->parents as $p)
				$txt .= sprintf("<li><a href='?url=%s'>%s</a></li>\n", urlencode($p['url']), $p['name']);
			$txt .= sprintf("</ul>\n");
		}

		if(isset($this->unions)) foreach($this->unions as $u){
			$txt .= sprintf("<li>Mariage : <a href='?url=%s'>%s</a></li>\n", urlencode($u['url']), $u['name']);
			if(count($u['childs']) > 0){
				$txt .= sprintf("<ul>\n");
				foreach($u['childs'] as $c){
					$txt .= sprintf("<li>%s - <a href='?url=%s'>%s</a></li>\n", $c['gender'], urlencode($c['url']), $c['name']);
				}
				$txt .= sprintf("</ul>\n");
			}
		}
		if(isset($this->sibling)){
			$txt .= sprintf("<li>Fratrie :</li>\n<ul>");
			foreach($this->sibling as $s)
				$txt .= sprintf("<li><a href='?url=%s'>%s</a></li>\n", urlencode($s['url']), $s['name']);
			$txt .= sprintf("</ul>\n");
		}
		
		if(isset($this->half_siblings)){
			$txt .= sprintf("<li>Demi-fratrie :</li>\n<ul>");
			foreach($this->half_siblings as $rec){
				$txt .= sprintf("<li><a href='?url=%s'>%s</a> + <a href='?url=%s'>%s</a></li>\n", 
						urlencode($rec['parents'][0]['url']), $rec['parents'][0]['name'],
						urlencode($rec['parents'][1]['url']), $rec['parents'][1]['name']
					);
				$txt .= sprintf("<ul>\n");
				
				foreach($rec['siblings'] as $p)
					$txt .= sprintf("<li><a href='?url=%s'>%s</a>\n", urlencode($p['url']), $p['name']);
				
				$txt .= sprintf("</ul>\n");
			}
			$txt .= sprintf("</ul>\n");
		}

		if(isset($this->notes)){
			$txt .= sprintf("<li>Notes :</li>\n<ul>");
			foreach($this->notes as $n)
				$txt .= sprintf("<li>%s</li>\n", $n);
			$txt .= sprintf("</ul>\n");
		}
		return $txt;
	}

	# get year from a variable format date
	function year($date, $lazy = false){
		if($date == null)
			return '?';

		if($lazy && preg_match('#(about\s+(\d+))|(in\s+(\d+))|(before\s+(\d+))#', $date, $values)){
			return $date;
		}
		if(preg_match('#(1\d\d\d)|(2\d\d\d)#', $date, $values)){
			return $values[1];
		}
		return $date;
	}

	// TODO : handle date with more format using this method from Pierre Fauque.
	//
	//   Author  : Copyright Pierre FAUQUE, pierre@fauque.net, 29/10/2010.
	//     - my website  (http://www.fr-webdev.net)
	//     - PHP Classes (http://www.phpclasses.org)
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	// Possible formats to write dates
	// 02/11/1946 (dd/mm/aaaa) ; 02/30/1946 (mm/dd/aaaa) Format must be specified as member of this class
	// vers 1946 ; about 1946 ; environ 1946 ; env 1946 ; approx 1946
	// après 1946 ; apres 1946 ; after 1946 ; post 1946
	// avant 1946 ; before 1946 ; ant 1946
	// 1946 : en 1946 ; pendant 1946
	// janvier 1946 ; fév 1946
	// juillet 1946 ; vers septembre 1946
	function ___getDate($date) {
		$date = trim($date);
		if(preg_match('/^[0-9]{4}$/',$date)) { return $date; }       // only a year
		if(eregi("[0-9]{2}/[0-9]{2}/[0-9]{4}",$date)) {              // date with format 'dd/mm/aaaa' or 'mm/dd/aaaa'
			$months = array(1=>'JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
			$tdate = explode("/",$date);
			switch($this->fdate) {
				case "dd/mm/aaaa": return ($tdate[0]*1)." ".$months[($tdate[1]*1)]." ".$tdate[2]; break;
				case "mm/dd/aaaa": return ($tdate[1]*1)." ".$months[($tdate[0]*1)]." ".$tdate[2]; break;
			}
		}
		$date = str_replace($this->t_abt,"ABT",$date);               // ex: "vers juin 1946"   => "ABT juin 1946" ; "vers 1946" => "ABT 1946"
		$date = str_replace($this->t_aft,"AFT",$date);               // ex: "après 1946"       => "AFT 1946"
		$date = str_replace($this->t_bef,"BEF",$date);               // ex: "avant fév 1946"   => "BEF fév 1946"
		$date = str_replace($this->t_months1,$this->t_months,$date); // ex: "ABT juillet 1946" => "ABT JUL 1940"
		$date = str_replace($this->t_months2,$this->t_months,$date); // ex: "BEF fév 1946"     => "BEF FEB 1942"
		return trim($date);
	}

}


?>
