<?php

/* Geneanet Page live browser, decoder */

error_reporting(E_ALL);

ini_set('include_path', '.:lib' . ini_get('include_path'));

require_once('lib/Config.php');
require_once('lib/Person.php');
require_once('lib/Geneanet.php');
require_once('lib/DbCache.php');


$geneanet = new GeneanetServer();

if(!$geneanet->login($config->get('connexion/user'), $config->get('connexion/passwd'))){
	printf($geneanet->last_error() . "\n");
	exit(0);
}

$grabber = new Grabber($geneanet);
$grabber->set_delay($config->get('grabber/delay'));
if($config->get('connexion/proxy') != '')
	$grabber->set_proxy($config->get('connexion/proxy'));


$cache = new DbCache('var/cache.sqlite');


$parser = new GeneanetEntryParser();

if(isset($_REQUEST['url']))
	$url = sprintf("http://gw3.geneanet.org/%s", $_REQUEST['url']);
else
	$url = $config->get('geneanet/default-url');

printf("url = '%s'<br>\n", $url);

$html = $cache->get_from_cache($url, 3600);
if($html === false){
	$html = $geneanet->get($url);
	if(preg_match('#Bad Request#i', $html))
		$html == false;
	else
		$cache->insert_into_cache($url, $html);
}

if($html !== false)
	$person = $parser->parse($html);

?>
<table>
<tr>
<td style="vertical-align:top;">

	<?php if($html !== false)
			echo $person->to_html(); ?>
	<pre>
	<?php 
		// print_r($person);
	 ?>
	</pre>
</td>
<td style="vertical-align:top;" width="70%">
	<frame>
		<?php if($html !== false)
				echo $html;
			else
				echo "bad request : $url";
		?>
	</frame>
</td>
</tr>
</table>

?>
