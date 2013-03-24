<?php

global $project;
$project = 'mysite';

global $database;
$database = '';

require_once('conf/ConfigureFromEnv.php');

global $databaseConfig;
$databaseConfig['memory'] = true;
$databaseConfig['path'] = dirname(dirname(__FILE__)) .'/assets/';