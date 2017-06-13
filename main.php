<?php

use SilverStripe\Core\AppKernel;
use SilverStripe\Core\HTTPApplication;
use SilverStripe\Core\Startup\ErrorControlChainMiddleware;
use SilverStripe\Core\Startup\OutputMiddleware;
use SilverStripe\Control\HTTPRequest;

require __DIR__ . '/src/includes/autoload.php';

// Build request and detect flush
$request = HTTPRequest::createFromEnvironment();
$flush = $request->getVar('flush') || strpos($request->getURL(), 'dev/build') === 0;

// Default application
$kernel = new AppKernel($flush);
$app = new HTTPApplication($kernel);
$app->addMiddleware(new OutputMiddleware());
$app->addMiddleware(new ErrorControlChainMiddleware($app, $request));
$app->handle($request);
