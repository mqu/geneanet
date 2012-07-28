<?php

error_reporting(E_ALL);

/* geany_encoding=ISO-8859-15 */

require_once('lib/Config.php');
require_once('lib/Person.php');
require_once('lib/Geneanet.php');
require_once('lib/Grabber.php');
require_once('lib/GedcomWriter.php');

$config = new Config();
$geneanet = new GeneanetServer();

if(!$geneanet->login($config->get('connexion/user'), $config->get('connexion/passwd'))){
	printf($geneanet->last_error() . "\n");
	exit(0);
}

if(isset($argv[1]))
	$url = $argv[1];
else
	$url = $config->get('geneanet/default-url');

$grabber = new Grabber($geneanet);
$grabber->set_delay($config->get('grabber/delay'));
if($config->get('connexion/proxy') != '')
	$grabber->set_proxy($config->get('connexion/proxy'));

$test = 'descendants';

switch($test){

case 'single':
	$p = $grabber->grab_single($url);
	print_r($p);
	echo utf8_decode($p);
	break;

case 'ascendants':
	$p = $grabber->grab_single($url);
	$grabber->grab_ascendants($p, $level=3);
	break;

case 'descendants':
	$p = $grabber->grab_single($url);
	$grabber->grab_descendants($p, $level=2);
	break;

}

# print_r($p);

$writer = new GedcomWriter($config);

# for debug
# echo utf8_decode($writer->pretty($writer->write($p)));

# it seems that geneanet do not support UTF8
switch($config->get('gedcom/charset')){
	case 'UTF-8':
	case 'UTF8':
		echo $writer->write($p);
		break;
	default:
		# default charset to ISO8859-15 (ok on Linux)
		echo utf8_decode($writer->write($p));
		break;
}

# echo $writer->write($p);

?>
