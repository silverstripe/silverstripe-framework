<?php

// Bootstrap for running SapphireTests

// Connect to database
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DB;

require_once '../../Core/Core.php';
require_once '../FakeController.php';

global $databaseConfig;
DB::connect($databaseConfig);

// Now set a fake REQUEST_URI
$_SERVER['REQUEST_URI'] = BASE_URL;

// Fake a session
$_SESSION = null;

// Prepare manifest autoloader
$controller = new FakeController();

SapphireTest::use_test_manifest();

SapphireTest::set_is_running_test(true);

// Remove the error handler so that PHPUnit can add its own
restore_error_handler();
