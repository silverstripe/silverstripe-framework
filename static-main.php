<?php

$domainBasedCaching = true;

if ($domainBasedCaching) {
	// Host -> cache dir mapping
	if (file_exists('../subsites/host-map.php')) {
		include_once '../subsites/host-map.php';
	} else {
		$subsiteHostmap = array();
	}
	
	// Look for the host, and find the cache dir
	$host = str_replace('www.', '', $_SERVER['HTTP_HOST']);
	$cacheDir = isset($siteHostmap[$host]) ? $siteHostmap[$host] : $siteHostmap['default'];
} else {
	$cacheDir = '';
}

// Look for the file in the cachedir
$file = preg_replace('/[^a-zA-Z0-9]/si', '_', trim($_SERVER['REQUEST_URI'], '/'));
$file = $file ? $file : 'index';
	
if (file_exists('../cache/'.$cacheDir.'/'.$file.'.html')) {
	echo file_get_contents('../cache/'.$cacheDir.'/'.$file.'.html');
} elseif (file_exists('../cache/'.$cacheDir.'/'.$file.'.php')) {
	include_once '../cache/'.$cacheDir.'/'.$file.'.php';
} else {
	// No cache hit... fallback!!!
	include 'main.php';
}

?>