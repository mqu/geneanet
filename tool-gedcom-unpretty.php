<?php

error_reporting(E_ALL);

require_once('lib/GedcomWriter.php');


if(isset($argv[1]))
	$file = $argv[1];
else{
	usage();
	exit(0);
}

$writer = new GedcomWriter($cnf=null);

echo $writer->unpretty(file_get_contents($file));


?>
