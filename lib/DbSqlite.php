<?php

/**
 * 
 *
 * @version $Id$
 * @copyright 2004 
 **/

# a simple Sqlite Db interface

# if(function_exists('sqlite_open')) {
if(!class_exists('SQLite3')) {
class DbSqlite{
  var $filename;
  var $db;

  function DbSqlite($filename, $sql_file = ''){
    if(!file_exists($filename))
	  $need_to_create = true;
	else
	  $need_to_create = false;
    $this->filename = $filename;
	$this->db = sqlite_open($this->filename);
	if($this->db == false){
	  $this->error('Sqlite::open()');
	}
	
	if($need_to_create && $sql_file != '')
	  $this->create($sql_file);
	  
	# speedup insertions
	# $this->query('PRAGMA default_synchronous = OFF');
  }

  function query($sql){
    $res = sqlite_query($this->db, $sql);
	if($res == false)
	  $this->error('Sqlite::query');
	return $res;
  }

  function multi_query($sql_list){
    $list = preg_split("#\n#", $sql_list);
	foreach($list as $sql){
		$sql = trim($sql);
		if(strlen($sql) > 0)
			$this->query($sql);
		
	}
  }


  function exec($sql){
    $res = sqlite_exec($this->db, $sql);
	if($res == false)
	  $this->error('Sqlite::exec');
	return $res;
  }


  function close(){
  	sqlite_close($this->db);
  }

  function get_list($sql){
    $res = $this->query($sql);
    $res = sqlite_fetch_all($res, SQLITE_NUM);
	$list = array();
	foreach ($res as $val){
	  $list[] = $val[0];
	}
	return $list;
  }

  function get_array($sql){
    $res = $this->query($sql);
    return (sqlite_fetch_all($res, SQLITE_ASSOC));
  }

  function get_one($sql){
    $res = $this->query($sql);
    return sqlite_fetch_single($res);
  }
  function list_tables(){
    $sql = "SELECT name FROM sqlite_master WHERE type='table'";
	return $this->get_list($sql);
  }

  function create($file){
    $content = file_get_contents($file);
	$lines = split("\n", $content);
	foreach ($lines as $line){
	 $line = preg_replace("/^#.*/", '', $line);
	 $line = trim($line);
	}

	$content = join("\n", $lines);
	$lines = split(';', $content);
	
	foreach ($lines as $line){
	 $line = preg_replace("/^#.*/", '', $line);
	 $line = trim($line);
	 $len = strlen($line);
	 if($len > 0 && $line != ';'){
		$this->query($line);
	 }
	}
  }

  function fetch_array($res){
    return sqlite_fetch_array($res, SQLITE_ASSOC);
  }

	public function field_update_by_key($db, $key, $id, $field, $value, $type='string'){
		switch($type){
			case 'string':
				$sql = sprintf("UPDATE plane SET %s='%s' WHERE %s='%s';", $field, $value, $key, $id);
				break;

		}
		$db->query($sql);
	}

  function error($str){
    trigger_error($str . sqlite_error_string(sqlite_last_error()));
  }

  function escape_string($str){
	return sqlite_escape_string($str);
	# return addslashes($str);
  }
}
} else {
	
class DbSqlite{
  var $filename;
  var $db;

  function DbSqlite($filename, $sql_file = ''){
    if(!file_exists($filename))
	  $need_to_create = true;
	else
	  $need_to_create = false;
    $this->filename = $filename;
	$this->db = new SQLite3($filename);
	if($this->db == false){
	  $this->error('Sqlite::open()');
	}
	
	if($need_to_create && $sql_file != '')
	  $this->create($sql_file);
	  
	# speedup insertions
	# $this->query('PRAGMA default_synchronous = OFF');
  }

  function query($sql){
    $res = $this->db->query($sql);
	if($res == false)
	  $this->error('Sqlite::query');
	return $res;
  }

  function multi_query($sql_list){
	throw new Exception ("not implemented");
  }


  function exec($sql){
    $res = $this->db->exec($sql);
	if($res == false)
	  $this->error('Sqlite::exec');
	return $res;
  }


  function close(){
  	$this->db->close();
  }

  function get_list($sql){
	throw new Exception ("not implemented");

  }

  function get_array($sql){
	$res = $this->query($sql);
	# print_r($res);
    return  $res->fetchArray(SQLITE3_ASSOC);
  }

  function get_one($sql){	
	$res = $this->query($sql);
	# print_r($res);
    $data =  $res->fetchArray(SQLITE3_NUM);
    # print_r($result);
    return $data[0];
  }

  function list_tables(){
	throw new Exception ("not implemented");
  }

  function create($file){
    $content = file_get_contents($file);
	$lines = split("\n", $content);
	foreach ($lines as $line){
	 $line = preg_replace("/^#.*/", '', $line);
	 $line = trim($line);
	}

	$content = join("\n", $lines);
	$lines = split(';', $content);
	
	foreach ($lines as $line){
	 $line = preg_replace("/^#.*/", '', $line);
	 $line = trim($line);
	 $len = strlen($line);
	 if($len > 0 && $line != ';'){
		$this->query($line);
	 }
	}
  }

  function fetch_array($res){
	throw new Exception ("not implemented");
  }

	public function field_update_by_key($db, $key, $id, $field, $value, $type='string'){
	  throw new Exception ("not implemented");
	}

  function error($str){
    throw new Exception ("not implemented");
  }

  function escape_string($str){
		return $this->db->escapeString($str);
  }
} # class
	
} # if function_exists()

?>
