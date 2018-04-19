<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.5.0 or higher.                                           **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

namespace SilverStripe\Dev\Install;

// Back up original ini config
$originalIni = [];
$iniSet = function ($name, $value) use (&$originalIni) {
    if (!isset($originalIni[$name])) {
        $originalIni[$name] = ini_get($name);
    }
    ini_set($name, $value);
};

// speed up mysql_connect timeout if the server can't be found
$iniSet('mysql.connect_timeout', 5);
// Don't die half was through installation; that does more harm than good
$iniSet('max_execution_time', 0);

// set display_errors php setting to on to force installer to avoid blank screen of death.
// get the original value so it can be used in PHP requirement checks later in this script.
$iniSet('display_errors', '1');

error_reporting(E_ALL | E_STRICT);

// Attempt to start a session so that the username and password can be sent back to the user.
if (function_exists('session_start') && !session_id()) {
    session_start();
}

// require composers autoloader
require_once __DIR__ . '/../../includes/autoload.php';

$usingEnv = empty($_POST) || !empty($_REQUEST['useEnv']);

// Set default locale, but try and sniff from the user agent
$locale = isset($_POST['locale']) ? $_POST['locale'] : 'en_US';

// Discover which databases are available
DatabaseAdapterRegistry::autodiscover();

// Determine which external database modules are USABLE
$databaseClasses = DatabaseAdapterRegistry::get_adapters();
foreach ($databaseClasses as $class => $details) {
    $helper = DatabaseAdapterRegistry::getDatabaseConfigurationHelper($class);
    $databaseClasses[$class]['hasModule'] = !empty($helper);
}

// Build config from config / environment / request
$config = new InstallConfig();
$databaseConfig = $config->getDatabaseConfig($_REQUEST, $databaseClasses, true);
$adminConfig = $config->getAdminConfig($_REQUEST, true);
$alreadyInstalled = $config->alreadyInstalled();
$silverstripe_version = $config->getFrameworkVersion();
$sendStats = $config->canSendStats($_REQUEST);
$locales = $config->getLocales();
$theme = $config->getTheme($_REQUEST);

// Check requirements
$req = new InstallRequirements();
$req->check($originalIni);

if ($req->isIIS()) {
    $webserverConfigFile = 'web.config';
} else {
    $webserverConfigFile = '.htaccess';
}

$hasErrorOtherThanDatabase = false;
$hasOnlyWarnings = false;
$phpIniLocation = php_ini_loaded_file();
if ($req->hasErrors()) {
    $hasErrorOtherThanDatabase = true;
} elseif ($req->hasWarnings()) {
    $hasOnlyWarnings = true;
}

$dbReq = new InstallRequirements();
if ($databaseConfig) {
    $dbReq->checkDatabase($databaseConfig);
}

$adminReq = new InstallRequirements();
if ($adminConfig) {
    $adminReq->checkAdminConfig($adminConfig);
}

// Actual processor
$installFromCli = (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'install');

// CLI-install error message.  exit(1) will halt any makefile.
if ($installFromCli && ($req->hasErrors() || $dbReq->hasErrors())) {
    echo "Cannot install due to errors:\n";
    $req->listErrors();
    $dbReq->listErrors();
    exit(1);
}

// Path to client resources (copied through silverstripe/vendor-plugin)
$base = rtrim(BASE_URL, '/') . '/';
$clientPath = PUBLIC_DIR
    ? 'resources/vendor/silverstripe/framework/src/Dev/Install/client'
    : 'resources/silverstripe/framework/src/Dev/Install/client';

// If already installed, ensure the user clicked "reinstall"
$expectedArg = $alreadyInstalled ? 'reinstall' : 'go';
if ((isset($_REQUEST[$expectedArg]) || $installFromCli)
    && !$req->hasErrors()
    && !$dbReq->hasErrors()
    && $adminConfig['username']
    && $adminConfig['password']
) {
    // Confirm before reinstalling
    $inst = new Installer();
    $inst->install([
        'usingEnv' => $usingEnv,
        'locale' => $locale,
        'theme' => $theme,
        'version' => $silverstripe_version,
        'db' => $databaseConfig,
        'admin' => $adminConfig,
        'stats' => $sendStats,
    ]);
    return;
}

// Sanitise config prior to rendering config-form.html
$databaseConfig = $config->getDatabaseConfig($_REQUEST, $databaseClasses, false);
$adminConfig = $config->getAdminConfig($_REQUEST, false);

// config-form.html vars (placeholder to prevent deletion)
[
    $base,
    $theme,
    $clientPath,
    $adminConfig,
    $databaseConfig,
    $usingEnv,
    $silverstripe_version,
    $locale,
    $locales,
    $webserverConfigFile,
    $hasErrorOtherThanDatabase,
    $hasOnlyWarnings, // If warnings but not errors
    $phpIniLocation,
];

include(__DIR__ . '/config-form.html');
