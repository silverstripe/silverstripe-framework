<?php

// CLI specific bootstrapping
use SilverStripe\Core\AppKernel;
use SilverStripe\Core\HTTPApplication;
use SilverStripe\Core\Startup\OutputMiddleware;
use SilverStripe\Control\HTTPRequest;

require __DIR__ . '/src/includes/cli.php';
require __DIR__ . '/src/includes/autoload.php';

// Build request and detect flush
$request = HTTPRequest::createFromEnvironment();

// Default application
$kernel = new AppKernel();
$app = new HTTPApplication($kernel);
$app->addMiddleware(new OutputMiddleware());
$app->handle($request);
