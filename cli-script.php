<?php

// CLI specific bootstrapping
use SilverStripe\Control\CLIRequestBuilder;
use SilverStripe\Control\HTTPApplication;
use SilverStripe\Core\CoreKernel;

require __DIR__ . '/src/includes/autoload.php';

// Ensure that people can't access this from a web-server
if (!in_array(PHP_SAPI, ["cli", "cgi", "cgi-fcgi"])) {
    echo "cli-script.php can't be run from a web request, you have to run it on the command-line.";
    die();
}

// Build request and detect flush
$request = CLIRequestBuilder::createFromEnvironment();

// Default application
$kernel = new CoreKernel(BASE_PATH);
$app = new HTTPApplication($kernel);
$response = $app->handle($request);
$response->output();
