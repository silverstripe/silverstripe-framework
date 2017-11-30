<?php

if (PHP_MAJOR_VERSION < 7) {
	spl_autoload_register('php5_compat_autoloader');
	spl_autoload_register('php7_compat_autoloader');
} elseif (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION < 2) {
	spl_autoload_register('php7_compat_autoloader');
}

function php5_compat_autoloader($classname) {
	switch (strtolower($classname)) {
		case 'int':
			require_once BASE_PATH . '/framework/model/fieldtypes/compat/Int.php';
			break;
		case 'float':
			require_once BASE_PATH . '/framework/model/fieldtypes/compat/Float.php';
			break;
	}
}

function php7_compat_autoloader($classname) {
	if (strcasecmp($classname, 'object') === 0) {
		require_once BASE_PATH . '/framework/core/compat/Object.php';
	}
}
