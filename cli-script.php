<?php

// CLI specific bootstrapping
use SilverStripe\Core\AppKernel;
use SilverStripe\Core\HTTPApplication;
use SilverStripe\Core\Startup\OutputMiddleware;
use SilverStripe\Control\HTTPRequest;

require __DIR__ . '/src/includes/cli.php';
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
chdir(__DIR__);


require __DIR__ . '/src/includes/autoload.php';

// Default application
$request = HTTPRequest::createFromEnvironment();
$kernel = new AppKernel();
$app = new HTTPApplication($kernel);
$app->addMiddleware(new OutputMiddleware());
$app->handle($request);
