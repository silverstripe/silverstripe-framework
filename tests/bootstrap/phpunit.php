<?php

// Bootstrap for running SapphireTests

// Connect to database
use SilverStripe\ORM\DB;

require_once __DIR__ . '/../../src/Core/Core.php';
require_once __DIR__ . '/../php/Control/FakeController.php';

// Bootstrap a mock project configuration
require __DIR__ . '/mysite.php';

global $databaseConfig;
DB::connect($databaseConfig);

// Now set a fake REQUEST_URI
$_SERVER['REQUEST_URI'] = BASE_URL;

// Fake a session
$_SESSION = null;

// Remove the error handler so that PHPUnit can add its own
restore_error_handler();
