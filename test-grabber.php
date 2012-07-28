<?php

error_reporting(E_ALL);

require_once('lib/Config.php');
require_once('lib/Person.php');
require_once('lib/Geneanet.php');
require_once('lib/Grabber.php');

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

$test = 'single';

switch($test){

case 'single':
	$p = $grabber->grab_single($url);
	# print_r($p);
	echo utf8_decode($p);
	break;

case 'ascendants':
	$p = $grabber->grab_single($url);
	$list = $grabber->grab_ascendants($p, $level=15);
	break;

case 'descendants':
	$p = $grabber->grab_single($url);
	$grabber->grab_descendants($p, $level=14);
	break;

case 'siblings':
	$p = $grabber->grab_single($url);
	printf("siblings of : %s\n", utf8_decode($p->quick_display()));
	$list = $grabber->grab_siblings($p);
	foreach($list as $p) printf(" - %s\n", utf8_decode($p->quick_display()));
	break;
	
case 'half-siblings':
	$p = $grabber->grab_single($url);
	printf("half-siblings of : %s\n", utf8_decode($p->quick_display()));
	$list = $grabber->grab_half_siblings($p);
	foreach($list as $p) printf(" - %s\n", utf8_decode($p->quick_display()));
	break;
	
case 'unions':

	$p = $grabber->grab_single($url);

	$unions = $grabber->grab_unions_and_childs($p);
	printf("Unions with %s\n", utf8_decode($p->quick_display()));
	foreach($unions as $u){
		printf("- union : %s\n", utf8_decode($u['spouse']->quick_display()));
		foreach($u['childs'] as $c){
			printf("  - %s\n", utf8_decode($c->quick_display()));
		}
	}
	break;

}

?>
