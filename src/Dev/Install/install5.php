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

/**
 * SilverStripe CMS SilverStripe\Dev\Install\Installer
 * This installer doesn't use any of the fancy SilverStripe stuff in case it's unsupported.
 */

// speed up mysql_connect timeout if the server can't be found
ini_set('mysql.connect_timeout', 5);
// Don't die half was through installation; that does more harm than good
ini_set('max_execution_time', 0);

// set display_errors php setting to on to force installer to avoid blank screen of death.
// get the original value so it can be used in PHP requirement checks later in this script.
$originalDisplayErrorsValue = ini_get('display_errors');
ini_set('display_errors', '1');

error_reporting(E_ALL | E_STRICT);

// Attempt to start a session so that the username and password can be sent back to the user.
if (function_exists('session_start') && !session_id()) {
    session_start();
}

// require composers autoloader
require __DIR__ . '/../../includes/autoload.php';

$usingEnv = !empty($_REQUEST['useEnv']);

// Set default locale, but try and sniff from the user agent
$defaultLocale = 'en_US';
$locales = array(
    'af_ZA' => 'Afrikaans (South Africa)',
    'ar_EG' => 'Arabic (Egypt)',
    'hy_AM' => 'Armenian (Armenia)',
    'ast_ES' => 'Asturian (Spain)',
    'az_AZ' => 'Azerbaijani (Azerbaijan)',
    'bs_BA' => 'Bosnian (Bosnia and Herzegovina)',
    'bg_BG' => 'Bulgarian (Bulgaria)',
    'ca_ES' => 'Catalan (Spain)',
    'zh_CN' => 'Chinese (China)',
    'zh_TW' => 'Chinese (Taiwan)',
    'hr_HR' => 'Croatian (Croatia)',
    'cs_CZ' => 'Czech (Czech Republic)',
    'da_DK' => 'Danish (Denmark)',
    'nl_NL' => 'Dutch (Netherlands)',
    'en_GB' => 'English (United Kingdom)',
    'en_US' => 'English (United States)',
    'eo_XX' => 'Esperanto',
    'et_EE' => 'Estonian (Estonia)',
    'fo_FO' => 'Faroese (Faroe Islands)',
    'fi_FI' => 'Finnish (Finland)',
    'fr_FR' => 'French (France)',
    'de_DE' => 'German (Germany)',
    'el_GR' => 'Greek (Greece)',
    'he_IL' => 'Hebrew (Israel)',
    'hu_HU' => 'Hungarian (Hungary)',
    'is_IS' => 'Icelandic (Iceland)',
    'id_ID' => 'Indonesian (Indonesia)',
    'it_IT' => 'Italian (Italy)',
    'ja_JP' => 'Japanese (Japan)',
    'km_KH' => 'Khmer (Cambodia)',
    'lc_XX' => 'LOLCAT',
    'lv_LV' => 'Latvian (Latvia)',
    'lt_LT' => 'Lithuanian (Lithuania)',
    'ms_MY' => 'Malay (Malaysia)',
    'mi_NZ' => 'Maori (New Zealand)',
    'ne_NP' => 'Nepali (Nepal)',
    'nb_NO' => 'Norwegian',
    'fa_IR' => 'Persian (Iran)',
    'pl_PL' => 'Polish (Poland)',
    'pt_BR' => 'Portuguese (Brazil)',
    'pa_IN' => 'Punjabi (India)',
    'ro_RO' => 'Romanian (Romania)',
    'ru_RU' => 'Russian (Russia)',
    'sr_RS' => 'Serbian (Serbia)',
    'si_LK' => 'Sinhalese (Sri Lanka)',
    'sk_SK' => 'Slovak (Slovakia)',
    'sl_SI' => 'Slovenian (Slovenia)',
    'es_AR' => 'Spanish (Argentina)',
    'es_MX' => 'Spanish (Mexico)',
    'es_ES' => 'Spanish (Spain)',
    'sv_SE' => 'Swedish (Sweden)',
    'th_TH' => 'Thai (Thailand)',
    'tr_TR' => 'Turkish (Turkey)',
    'uk_UA' => 'Ukrainian (Ukraine)',
    'uz_UZ' => 'Uzbek (Uzbekistan)',
    'vi_VN' => 'Vietnamese (Vietnam)',
);

// Discover which databases are available
DatabaseAdapterRegistry::autodiscover();

// Determine which external database modules are USABLE
$databaseClasses = DatabaseAdapterRegistry::get_adapters();
foreach ($databaseClasses as $class => $details) {
    $helper = DatabaseAdapterRegistry::getDatabaseConfigurationHelper($class);
    $databaseClasses[$class]['hasModule'] = !empty($helper);
}

// Load database config
if (isset($_REQUEST['db'])) {
    if (isset($_REQUEST['db']['type'])) {
        $type = $_REQUEST['db']['type'];
    } else {
        if ($type = getenv('SS_DATABASE_CLASS')) {
            $_REQUEST['db']['type'] = $type;
        } elseif ($databaseClasses['MySQLPDODatabase']['supported']) {
            $type = $_REQUEST['db']['type'] = 'MySQLPDODatabase';
        } elseif ($databaseClasses['MySQLDatabase']['supported']) {
            $type = $_REQUEST['db']['type'] = 'MySQLDatabase';
        } else {
            // handle error
        }
    }

    // Disabled inputs don't submit anything - we need to use the environment (except the database name)
    if ($usingEnv) {
        $_REQUEST['db'][$type] = $databaseConfig = array(
            "type" => getenv('SS_DATABASE_CLASS') ?: $type,
            "server" => getenv('SS_DATABASE_SERVER') ?: "localhost",
            "username" => getenv('SS_DATABASE_USERNAME') ?: "root",
            "password" => getenv('SS_DATABASE_PASSWORD') ?: "",
            "database" => $_REQUEST['db'][$type]['database'],
        );
    } else {
        // Normal behaviour without the environment
        $databaseConfig = $_REQUEST['db'][$type];
        $databaseConfig['type'] = $type;
    }
} else {
    if ($type = getenv('SS_DATABASE_CLASS')) {
        $_REQUEST['db']['type'] = $type;
    } elseif ($databaseClasses['MySQLPDODatabase']['supported']) {
        $type = $_REQUEST['db']['type'] = 'MySQLPDODatabase';
    } elseif ($databaseClasses['MySQLDatabase']['supported']) {
        $type = $_REQUEST['db']['type'] = 'MySQLDatabase';
    } else {
        // handle error
    }
    $_REQUEST['db'][$type] = $databaseConfig = array(
        "type" => $type,
        "server" => getenv('SS_DATABASE_SERVER') ?: "localhost",
        "username" => getenv('SS_DATABASE_USERNAME') ?: "root",
        "password" => getenv('SS_DATABASE_PASSWORD') ?: "",
        "database" => isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : "SS_mysite",
    );
}

if (isset($_REQUEST['admin'])) {
    // Disabled inputs don't submit anything - we need to use the environment (except the database name)
    if ($usingEnv) {
        $_REQUEST['admin'] = $adminConfig = array(
            'username' => getenv('SS_DEFAULT_ADMIN_USERNAME') ?: 'admin',
            'password' => getenv('SS_DEFAULT_ADMIN_PASSWORD') ?: '',
        );
    } else {
        $adminConfig = $_REQUEST['admin'];
    }
} else {
    $_REQUEST['admin'] = $adminConfig = array(
        'username' => getenv('SS_DEFAULT_ADMIN_USERNAME') ?: 'admin',
        'password' => getenv('SS_DEFAULT_ADMIN_PASSWORD') ?: '',
    );
}

$alreadyInstalled = false;
if (file_exists('mysite/_config.php')) {
    // Find the $database variable in the relevant config file without having to execute the config file
    if (preg_match("/\\\$database\s*=\s*[^\n\r]+[\n\r]/", file_get_contents("mysite/_config.php"), $parts)) {
        eval($parts[0]);
        if (!empty($database)) {
            $alreadyInstalled = true;
        }
        // Assume that if $databaseConfig is defined in mysite/_config.php, then a non-environment-based installation has
        // already gone ahead
    } elseif (preg_match(
        "/\\\$databaseConfig\s*=\s*[^\n\r]+[\n\r]/",
        file_get_contents("mysite/_config.php"),
        $parts
    )) {
        $alreadyInstalled = true;
    }
}

if (file_exists(FRAMEWORK_NAME . '/silverstripe_version')) {
    $silverstripe_version = file_get_contents(FRAMEWORK_NAME . '/silverstripe_version');
} else {
    $silverstripe_version = "unknown";
}

// Check requirements
$req = new InstallRequirements();
$req->check();

$webserverConfigFile = '';
if ($req->isIIS()) {
    $webserverConfigFile = 'web.config';
} else {
    $webserverConfigFile = '.htaccess';
}

if ($req->hasErrors()) {
    $hasErrorOtherThanDatabase = true;
    $phpIniLocation = php_ini_loaded_file();
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

if ((isset($_REQUEST['go']) || $installFromCli)
    && !$req->hasErrors()
    && !$dbReq->hasErrors()
    && $adminConfig['username']
    && $adminConfig['password']
) {
    // Confirm before reinstalling
    if (!$installFromCli && $alreadyInstalled) {
        include(__DIR__ . '/config-form.html');
    } else {
        $inst = new Installer();
        if ($_REQUEST) {
            $inst->install($_REQUEST);
        } else {
            $inst->install(array(
                'db' => $databaseConfig,
                'admin' => $adminConfig,
            ));
        }
    }

// Show the config form
} else {
    include(__DIR__ . '/config-form.html');
}

