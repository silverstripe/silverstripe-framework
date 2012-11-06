<?php

global $project;
$project = 'mysite';

global $database;
$database = '';

require_once('conf/ConfigureFromEnv.php');

global $databaseConfig;
$databaseConfig['memory'] = true;
$databaseConfig['path'] = dirname(dirname(__FILE__)) .'/assets/';

MySQLDatabase::set_connection_charset('utf8');

// Set the current theme. More themes can be downloaded from
// http://www.silverstripe.org/themes/
SSViewer::set_theme('simple');