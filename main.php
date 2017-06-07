<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.6.0 or higher                                            **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

use SilverStripe\Core\AppKernel;
use SilverStripe\Core\HTTPApplication;
use SilverStripe\Core\Startup\ErrorControlChainMiddleware;
use SilverStripe\Core\Startup\OutputMiddleware;
use SilverStripe\Control\HTTPRequest;

require __DIR__ . '/src/includes/autoload.php';

// Default application
$request = HTTPRequest::createFromEnvironment();
$kernel = new AppKernel();
$app = new HTTPApplication($kernel);
$app->addMiddleware(new OutputMiddleware());
$app->addMiddleware(new ErrorControlChainMiddleware($app, $request));
$app->handle($request);
