<?php

if (PHP_MAJOR_VERSION < 7) {
	spl_autoload_register('php5_compat_autoloader');
}

function php5_compat_autoloader($classname) {
	$classMap = array(
		"int" => "/framework/model/fieldtypes/compat/Int.php",
		"float" => "/framework/model/fieldtypes/compat/Float.php",
	);

	$classname = strtolower($classname);
	if(isset($classMap[$classname])) {
		require_once BASE_PATH . $classMap[$classname];
	}
}
