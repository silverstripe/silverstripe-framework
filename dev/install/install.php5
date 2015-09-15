<?php

/************************************************************************************
 ************************************************************************************
 **                                                                                **
 **  If you can read this text in your browser then you don't have PHP installed.  **
 **  Please install PHP 5.3.3 or higher, preferably PHP 5.3.4+.                    **
 **                                                                                **
 ************************************************************************************
 ************************************************************************************/

/**
 * SilverStripe CMS Installer
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
if(function_exists('session_start') && !session_id()) {
	session_start();
}

require_once FRAMEWORK_NAME . '/core/Constants.php'; // this also includes TempPath.php;

$envFileExists = defined('SS_ENVIRONMENT_FILE');
$usingEnv = $envFileExists && !empty($_REQUEST['useEnv']);

require_once FRAMEWORK_NAME . '/dev/install/DatabaseConfigurationHelper.php';
require_once FRAMEWORK_NAME . '/dev/install/DatabaseAdapterRegistry.php';

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
foreach(DatabaseAdapterRegistry::get_adapters() as $class => $details) {
	$databaseClasses[$class] = $details;
	if(file_exists($details['helperPath'])) {
		$databaseClasses[$class]['hasModule'] = true;
		include_once($details['helperPath']);
	} else {
		$databaseClasses[$class]['hasModule'] = false;
	}
}

// Load database config
if(isset($_REQUEST['db'])) {
	if(isset($_REQUEST['db']['type'])) {
		$type = $_REQUEST['db']['type'];
	} else {
		$type = $_REQUEST['db']['type'] = defined('SS_DATABASE_CLASS') ? SS_DATABASE_CLASS : 'MySQLDatabase';
	}

	// Disabled inputs don't submit anything - we need to use the environment (except the database name)
	if($usingEnv) {
		$_REQUEST['db'][$type] = $databaseConfig = array(
			"type" => defined('SS_DATABASE_CLASS') ? SS_DATABASE_CLASS : $type,
			"server" => defined('SS_DATABASE_SERVER') ? SS_DATABASE_SERVER : "localhost",
			"username" => defined('SS_DATABASE_USERNAME') ? SS_DATABASE_USERNAME : "root",
			"password" => defined('SS_DATABASE_PASSWORD') ? SS_DATABASE_PASSWORD : "",
			"database" => $_REQUEST['db'][$type]['database'],
		);

	} else {
		// Normal behaviour without the environment
		$databaseConfig = $_REQUEST['db'][$type];
		$databaseConfig['type'] = $type;
	}
} else {
	$type = $_REQUEST['db']['type'] = defined('SS_DATABASE_CLASS') ? SS_DATABASE_CLASS : 'MySQLDatabase';
	$_REQUEST['db'][$type] = $databaseConfig = array(
		"type" => $type,
		"server" => defined('SS_DATABASE_SERVER') ? SS_DATABASE_SERVER : "localhost",
		"username" => defined('SS_DATABASE_USERNAME') ? SS_DATABASE_USERNAME : "root",
		"password" => defined('SS_DATABASE_PASSWORD') ? SS_DATABASE_PASSWORD : "",
		"database" => isset($_SERVER['argv'][2]) ? $_SERVER['argv'][2] : "SS_mysite",
	);
}

if(isset($_REQUEST['admin'])) {
	// Disabled inputs don't submit anything - we need to use the environment (except the database name)
	if($usingEnv) {
		$_REQUEST['admin'] = $adminConfig = array(
			'username' => defined('SS_DEFAULT_ADMIN_USERNAME') ? SS_DEFAULT_ADMIN_USERNAME : 'admin',
			'password' => defined('SS_DEFAULT_ADMIN_PASSWORD') ? SS_DEFAULT_ADMIN_PASSWORD : '',
		);
	} else {
		$adminConfig = $_REQUEST['admin'];
	}
} else {
	$_REQUEST['admin'] = $adminConfig = array(
		'username' => defined('SS_DEFAULT_ADMIN_USERNAME') ? SS_DEFAULT_ADMIN_USERNAME : 'admin',
		'password' => defined('SS_DEFAULT_ADMIN_PASSWORD') ? SS_DEFAULT_ADMIN_PASSWORD : '',
	);
}

$alreadyInstalled = false;
if(file_exists('mysite/_config.php')) {
	// Find the $database variable in the relevant config file without having to execute the config file
	if(preg_match("/\\\$database\s*=\s*[^\n\r]+[\n\r]/", file_get_contents("mysite/_config.php"), $parts)) {
		eval($parts[0]);
		if($database) $alreadyInstalled = true;
		// Assume that if $databaseConfig is defined in mysite/_config.php, then a non-environment-based installation has
		// already gone ahead
	} else if(preg_match("/\\\$databaseConfig\s*=\s*[^\n\r]+[\n\r]/", file_get_contents("mysite/_config.php"), $parts)) {
		$alreadyInstalled = true;
	}
}

if(file_exists(FRAMEWORK_NAME . '/silverstripe_version')) {
	$silverstripe_version = file_get_contents(FRAMEWORK_NAME . '/silverstripe_version');
} else {
	$silverstripe_version = "unknown";
}

// Check requirements
$req = new InstallRequirements();
$req->check();

$webserverConfigFile = '';
if($req->isIIS()) {
	$webserverConfigFile = 'web.config';
} else {
	$webserverConfigFile = '.htaccess';
}

if($req->hasErrors()) {
	$hasErrorOtherThanDatabase = true;
	$phpIniLocation = php_ini_loaded_file();
}

if($databaseConfig) {
	$dbReq = new InstallRequirements();
	$dbReq->checkDatabase($databaseConfig);
}

if($adminConfig) {
	$adminReq = new InstallRequirements();
	$adminReq->checkAdminConfig($adminConfig);
}

// Actual processor
$installFromCli = (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] == 'install');

// CLI-install error message.  exit(1) will halt any makefile.
if($installFromCli && ($req->hasErrors() || $dbReq->hasErrors())) {
	echo "Cannot install due to errors:\n";
	$req->listErrors();
	$dbReq->listErrors();
	exit(1);
}

if((isset($_REQUEST['go']) || $installFromCli) && !$req->hasErrors() && !$dbReq->hasErrors() && $adminConfig['username'] && $adminConfig['password']) {
	// Confirm before reinstalling
	if(!$installFromCli && $alreadyInstalled) {
		include(FRAMEWORK_NAME . '/dev/install/config-form.html');

	} else {
		$inst = new Installer();
		if($_REQUEST) $inst->install($_REQUEST);
		else $inst->install(array(
			'db' => $databaseConfig,
			'admin' => $adminConfig,
		));
	}

// Show the config form
} else {
	include(FRAMEWORK_NAME . '/dev/install/config-form.html');
}

/**
 * This class checks requirements
 * Each of the requireXXX functions takes an argument which gives a user description of the test.
 * It's an array of 3 parts:
 *  $description[0] - The test catetgory
 *  $description[1] - The test title
 *  $description[2] - The test error to show, if it goes wrong
 */
class InstallRequirements {
	var $errors, $warnings, $tests;

	/**
	 * Check the database configuration. These are done one after another
	 * starting with checking the database function exists in PHP, and
	 * continuing onto more difficult checks like database permissions.
	 *
	 * @param array $databaseConfig The list of database parameters
	 * @return boolean Validity of database configuration details
	 */
	public function checkDatabase($databaseConfig) {
		// Check if support is available
		if(!$this->requireDatabaseFunctions(
			$databaseConfig,
			array(
				"Database Configuration",
				"Database support",
				"Database support in PHP",
				$this->getDatabaseTypeNice($databaseConfig['type'])
			)
		)) return false;

		// Check if the server is available
		$usePath = !empty($databaseConfig['path']) && empty($databaseConfig['server']);
		if(!$this->requireDatabaseServer(
			$databaseConfig,
			array(
				"Database Configuration",
				"Database server",
				$usePath ? "I couldn't write to path '$databaseConfig[path]'" : "I couldn't find a database server on '$databaseConfig[server]'",
				$usePath ? $databaseConfig['path'] : $databaseConfig['server']
			)
		)) return false;

		// Check if the connection credentials allow access to the server / database
		if(!$this->requireDatabaseConnection(
			$databaseConfig,
			array(
				"Database Configuration",
				"Database access credentials",
				"That username/password doesn't work"
			)
		)) return false;

		// Check the necessary server version is available
		if(!$this->requireDatabaseVersion(
			$databaseConfig,
			array(
				"Database Configuration",
				"Database server version requirement",
				'',
				'Version ' . $this->getDatabaseConfigurationHelper($databaseConfig['type'])->getDatabaseVersion($databaseConfig)
			)
		)) return false;

		// Check that database creation permissions are available
		if(!$this->requireDatabaseOrCreatePermissions(
			$databaseConfig,
			array(
				"Database Configuration",
				"Can I access/create the database",
				"I can't create new databases and the database '$databaseConfig[database]' doesn't exist"
			)
		)) return false;

		// Check alter permission (necessary to create tables etc)
		if(!$this->requireDatabaseAlterPermissions(
			$databaseConfig,
			array(
				"Database Configuration",
				"Can I ALTER tables",
				"I don't have permission to ALTER tables"
			)
		)) return false;

		// Success!
		return true;
	}

	public function checkAdminConfig($adminConfig) {
		if(!$adminConfig['username']) {
			$this->error(array('', 'Please enter a username!'));
		}
		if(!$adminConfig['password']) {
			$this->error(array('', 'Please enter a password!'));
		}
	}

	/**
	 * Check if the web server is IIS and version greater than the given version.
	 * @return boolean
	 */
	public function isIIS($fromVersion = 7) {
		if(strpos($this->findWebserver(), 'IIS/') === false) {
			return false;
		}
		return substr(strstr($this->findWebserver(), '/'), -3, 1) >= $fromVersion;
	}

	public function isApache() {
		if(strpos($this->findWebserver(), 'Apache') !== false) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Find the webserver software running on the PHP host.
	 * @return string|boolean Server software or boolean FALSE
	 */
	public function findWebserver() {
		// Try finding from SERVER_SIGNATURE or SERVER_SOFTWARE
		if(!empty($_SERVER['SERVER_SIGNATURE'])) {
			$webserver = $_SERVER['SERVER_SIGNATURE'];
		} elseif(!empty($_SERVER['SERVER_SOFTWARE'])) {
			$webserver = $_SERVER['SERVER_SOFTWARE'];
		} else {
			return false;
		}

		return strip_tags(trim($webserver));
	}

	/**
	 * Check everything except the database
	 */
	public function check() {
		$this->errors = null;
		$isApache = $this->isApache();
		$isIIS = $this->isIIS();
		$webserver = $this->findWebserver();

		$this->requirePHPVersion('5.3.4', '5.3.3', array(
			"PHP Configuration",
			"PHP5 installed",
			null,
			"PHP version " . phpversion()
		));

		// Check that we can identify the root folder successfully
		$this->requireFile(FRAMEWORK_NAME . '/dev/install/config-form.html', array("File permissions",
			"Does the webserver know where files are stored?",
			"The webserver isn't letting me identify where files are stored.",
			$this->getBaseDir()
		));

		$this->requireModule('mysite', array("File permissions", "mysite/ directory exists?"));
		$this->requireModule(FRAMEWORK_NAME, array("File permissions", FRAMEWORK_NAME . "/ directory exists?"));

		if($isApache) {
			$this->checkApacheVersion(array(
				"Webserver Configuration",
				"Webserver is not Apache 1.x", "SilverStripe requires Apache version 2 or greater",
				$webserver
			));
			$this->requireWriteable('.htaccess', array("File permissions", "Is the .htaccess file writeable?", null));
		} elseif($isIIS) {
			$this->requireWriteable('web.config', array("File permissions", "Is the web.config file writeable?", null));
		}

		$this->requireWriteable('mysite/_config.php', array(
			"File permissions",
			"Is the mysite/_config.php file writeable?",
			null
		));

		$this->requireWriteable('mysite/_config/config.yml', array(
			"File permissions",
			"Is the mysite/_config/config.yml file writeable?",
			null
		));

		if(!$this->checkModuleExists('cms')) {
			$this->requireWriteable('mysite/code/RootURLController.php', array(
				"File permissions",
				"Is the mysite/code/RootURLController.php file writeable?",
				null
			));
		}
		$this->requireWriteable('assets', array("File permissions", "Is the assets/ directory writeable?", null));

		try {
			$tempFolder = getTempFolder();
		} catch(Exception $e) {
			$tempFolder = false;
		}

		$this->requireTempFolder(array('File permissions', 'Is a temporary directory available?', null, $tempFolder));
		if($tempFolder) {
			// in addition to the temp folder being available, check it is writable
			$this->requireWriteable($tempFolder, array(
				"File permissions",
				sprintf("Is the temporary directory writeable?", $tempFolder),
				null
			), true);
		}

		// Check for web server, unless we're calling the installer from the command-line
		$this->isRunningWebServer(array("Webserver Configuration", "Server software", "Unknown", $webserver));

		if($isApache) {
			$this->requireApacheRewriteModule('mod_rewrite', array(
				"Webserver Configuration",
				"URL rewriting support",
				"You need mod_rewrite to use friendly URLs with SilverStripe, but it is not enabled."
			));
		} elseif($isIIS) {
			$this->requireIISRewriteModule('IIS_UrlRewriteModule', array(
				"Webserver Configuration",
				"URL rewriting support",
				"You need to enable the IIS URL Rewrite Module to use friendly URLs with SilverStripe, "
				. "but it is not installed or enabled. Download it for IIS 7 from http://www.iis.net/expand/URLRewrite"
			));
		} else {
			$this->warning(array(
				"Webserver Configuration",
				"URL rewriting support",
				"I can't tell whether any rewriting module is running.  You may need to configure a rewriting rule yourself."));
		}

		$this->requireServerVariables(array('SCRIPT_NAME', 'HTTP_HOST', 'SCRIPT_FILENAME'), array(
			"Webserver Configuration",
			"Recognised webserver",
			"You seem to be using an unsupported webserver.  "
			. "The server variables SCRIPT_NAME, HTTP_HOST, SCRIPT_FILENAME need to be set."
		));

		$this->requirePostSupport(array(
			"Webserver Configuration",
			"POST Support",
			'I can\'t find $_POST, make sure POST is enabled.'
		));

		// Check for GD support
		if(!$this->requireFunction("imagecreatetruecolor", array(
			"PHP Configuration",
			"GD2 support",
			"PHP must have GD version 2."
        ))) {
			$this->requireFunction("imagecreate", array(
				"PHP Configuration",
				"GD2 support",
				"GD support for PHP not included."
			));
		}

		// Check for XML support
		$this->requireFunction('xml_set_object', array(
			"PHP Configuration",
			"XML support",
			"XML support not included in PHP."
		));
		$this->requireClass('DOMDocument', array(
			"PHP Configuration",
			"DOM/XML support",
			"DOM/XML support not included in PHP."
		));
		$this->requireFunction('simplexml_load_file', array(
			'PHP Configuration',
			'SimpleXML support',
			'SimpleXML support not included in PHP.'
		));

		// Check for token_get_all
		$this->requireFunction('token_get_all', array(
			"PHP Configuration",
			"Tokenizer support",
			"Tokenizer support not included in PHP."
		));

		// Check for CType support
		$this->requireFunction('ctype_digit', array(
			'PHP Configuration',
			'CType support',
			'CType support not included in PHP.'
		));

		// Check for session support
		$this->requireFunction('session_start', array(
			'PHP Configuration',
			'Session support',
			'Session support not included in PHP.'
		));

		// Check for iconv support
		$this->requireFunction('iconv', array(
			'PHP Configuration',
			'iconv support',
			'iconv support not included in PHP.'
		));

		// Check for hash support
		$this->requireFunction('hash', array('PHP Configuration', 'hash support', 'hash support not included in PHP.'));

		// Check for mbstring support
		$this->requireFunction('mb_internal_encoding', array(
			'PHP Configuration',
			'mbstring support',
			'mbstring support not included in PHP.'
		));

		// Check for Reflection support
		$this->requireClass('ReflectionClass', array(
			'PHP Configuration',
			'Reflection support',
			'Reflection support not included in PHP.'
		));

		// Check for Standard PHP Library (SPL) support
		$this->requireFunction('spl_classes', array(
			'PHP Configuration',
			'SPL support',
			'Standard PHP Library (SPL) not included in PHP.'
		));

		$this->requireDateTimezone(array(
			'PHP Configuration',
			'date.timezone setting and validity',
			'date.timezone option in php.ini must be set correctly.',
			ini_get('date.timezone')
		));

		$this->suggestClass('finfo', array(
			'PHP Configuration',
			'fileinfo support',
			'fileinfo should be enabled in PHP. SilverStripe uses it for MIME type detection of files. '
			. 'SilverStripe will still operate, but email attachments and sending files to browser '
			. '(e.g. export data to CSV) may not work correctly without finfo.'
		));

		$this->suggestFunction('curl_init', array(
			'PHP Configuration',
			'curl support',
			'curl should be enabled in PHP. SilverStripe uses it for consuming web services'
			. ' via the RestfulService class and many modules rely on it.'
		));

		$this->suggestClass('tidy', array(
			'PHP Configuration',
			'tidy support',
			'Tidy provides a library of code to clean up your html. '
			. 'SilverStripe will operate fine without tidy but HTMLCleaner will not be effective.'
		));

		$this->suggestPHPSetting('asp_tags', array(false), array(
			'PHP Configuration',
			'asp_tags option',
			'This should be turned off as it can cause issues with SilverStripe'
		));
		$this->requirePHPSetting('magic_quotes_gpc', array(false), array(
			'PHP Configuration',
			'magic_quotes_gpc option',
			'This should be turned off, as it can cause issues with cookies. '
			. 'More specifically, unserializing data stored in cookies.'
		));
		$this->suggestPHPSetting('display_errors', array(false), array(
			'PHP Configuration',
			'display_errors option',
			'Unless you\'re in a development environment, this should be turned off, '
			. 'as it can expose sensitive data to website users.'
		));
		// on some weirdly configured webservers arg_separator.output is set to &amp;
		// which will results in links like ?param=value&amp;foo=bar which will not be i
		$this->suggestPHPSetting('arg_separator.output', array('&', ''), array(
			'PHP Configuration',
			'arg_separator.output option',
			'This option defines how URL parameters are concatenated. '
			. 'If not set to \'&\' this may cause issues with URL GET parameters'
		));

		// Check memory allocation
		$this->requireMemory(32 * 1024 * 1024, 64 * 1024 * 1024, array(
			"PHP Configuration",
			"Memory allocation (PHP config option 'memory_limit')",
			"SilverStripe needs a minimum of 32M allocated to PHP, but recommends 64M.",
			ini_get("memory_limit")
		));

		return $this->errors;
	}

	public function suggestPHPSetting($settingName, $settingValues, $testDetails) {
		$this->testing($testDetails);

		// special case for display_errors, check the original value before
		// it was changed at the start of this script.
		if($settingName == 'display_errors') {
			global $originalDisplayErrorsValue;
			$val = $originalDisplayErrorsValue;
		} else {
			$val = ini_get($settingName);
		}

		if(!in_array($val, $settingValues) && $val != $settingValues) {
			$testDetails[2] = "$settingName is set to '$val' in php.ini.  $testDetails[2]";
			$this->warning($testDetails);
		}
	}

	public function requirePHPSetting($settingName, $settingValues, $testDetails) {
		$this->testing($testDetails);

		$val = ini_get($settingName);
		if(!in_array($val, $settingValues) && $val != $settingValues) {
			$testDetails[2] = "$settingName is set to '$val' in php.ini.  $testDetails[2]";
			$this->error($testDetails);
		}
	}

	public function suggestClass($class, $testDetails) {
		$this->testing($testDetails);

		if(!class_exists($class)) {
			$this->warning($testDetails);
		}
	}

	public function suggestFunction($class, $testDetails) {
		$this->testing($testDetails);

		if(!function_exists($class)) {
			$this->warning($testDetails);
		}
	}

	public function requireDateTimezone($testDetails) {
		$this->testing($testDetails);

		$result = ini_get('date.timezone') && in_array(ini_get('date.timezone'), timezone_identifiers_list());
		if(!$result) {
			$this->error($testDetails);
		}
	}

	public function requireMemory($min, $recommended, $testDetails) {
		$_SESSION['forcemem'] = false;

		$mem = $this->getPHPMemory();
		if($mem < (64 * 1024 * 1024)) {
			ini_set('memory_limit', '64M');
			$mem = $this->getPHPMemory();
			$testDetails[3] = ini_get("memory_limit");
		}

		$this->testing($testDetails);

		if($mem < $min && $mem > 0) {
			$testDetails[2] .= " You only have " . ini_get("memory_limit") . " allocated";
			$this->error($testDetails);
		} else if($mem < $recommended && $mem > 0) {
			$testDetails[2] .= " You only have " . ini_get("memory_limit") . " allocated";
			$this->warning($testDetails);
		} elseif($mem == 0) {
			$testDetails[2] .= " We can't determine how much memory you have allocated. "
				. "Install only if you're sure you've allocated at least 20 MB.";
			$this->warning($testDetails);
		}
	}

	public function getPHPMemory() {
		$memString = ini_get("memory_limit");

		switch(strtolower(substr($memString, -1))) {
			case "k":
				return round(substr($memString, 0, -1) * 1024);

			case "m":
				return round(substr($memString, 0, -1) * 1024 * 1024);

			case "g":
				return round(substr($memString, 0, -1) * 1024 * 1024 * 1024);

			default:
				return round($memString);
		}
	}

	public function listErrors() {
		if($this->errors) {
			echo "<p>The following problems are preventing me from installing SilverStripe CMS:</p>\n\n";
			foreach($this->errors as $error) {
				echo "<li>" . htmlentities(implode(", ", $error), ENT_COMPAT, 'UTF-8') . "</li>\n";
			}
		}
	}

	public function showTable($section = null) {
		if($section) {
			$tests = $this->tests[$section];
			$id = strtolower(str_replace(' ', '_', $section));
			echo "<table id=\"{$id}_results\" class=\"testResults\" width=\"100%\">";
			foreach($tests as $test => $result) {
				echo "<tr class=\"$result[0]\"><td>$test</td><td>"
					. nl2br(htmlentities($result[1], ENT_COMPAT, 'UTF-8')) . "</td></tr>";
			}
			echo "</table>";

		} else {
			foreach($this->tests as $section => $tests) {
				$failedRequirements = 0;
				$warningRequirements = 0;

				$output = "";

				foreach($tests as $test => $result) {
					if(isset($result['0'])) {
						switch($result['0']) {
							case 'error':
								$failedRequirements++;
								break;
							case 'warning':
								$warningRequirements++;
								break;
						}
					}
					$output .= "<tr class=\"$result[0]\"><td>$test</td><td>"
						. nl2br(htmlentities($result[1], ENT_COMPAT, 'UTF-8')) . "</td></tr>";
				}
				$className = "good";
				$text = "All Requirements Pass";
				$pluralWarnings = ($warningRequirements == 1) ? 'Warning' : 'Warnings';

				if($failedRequirements > 0) {
					$className = "error";
					$pluralWarnings = ($warningRequirements == 1) ? 'Warning' : 'Warnings';

					$text = $failedRequirements . ' Failed and ' . $warningRequirements . ' ' . $pluralWarnings;
				} else if($warningRequirements > 0) {
					$className = "warning";
					$text = "All Requirements Pass but " . $warningRequirements . ' ' . $pluralWarnings;
				}

				echo "<h5 class='requirement $className'>$section <a href='#'>Show All Requirements</a> <span>$text</span></h5>";
				echo "<table class=\"testResults\">";
				echo $output;
				echo "</table>";
			}
		}
	}

	public function requireFunction($funcName, $testDetails) {
		$this->testing($testDetails);

		if(!function_exists($funcName)) {
			$this->error($testDetails);
		} else {
			return true;
		}
	}

	public function requireClass($className, $testDetails) {
		$this->testing($testDetails);
		if(!class_exists($className)) {
			$this->error($testDetails);
		} else {
			return false;
		}
	}

	/**
	 * Require that the given class doesn't exist
	 */
	public function requireNoClasses($classNames, $testDetails) {
		$this->testing($testDetails);
		$badClasses = array();
		foreach($classNames as $className) {
			if(class_exists($className)) $badClasses[] = $className;
		}
		if($badClasses) {
			$testDetails[2] .= ".  The following classes are at fault: " . implode(', ', $badClasses);
			$this->error($testDetails);
		} else {
			return true;
		}
	}

	public function checkApacheVersion($testDetails) {
		$this->testing($testDetails);

		$is1pointx = preg_match('#Apache[/ ]1\.#', $testDetails[3]);
		if($is1pointx) {
			$this->error($testDetails);
		}

		return true;
	}

	public function requirePHPVersion($recommendedVersion, $requiredVersion, $testDetails) {
		$this->testing($testDetails);

		$installedVersion = phpversion();

		if(version_compare($installedVersion, $requiredVersion, '<')) {
			$testDetails[2] = "SilverStripe requires PHP version $requiredVersion or later.\n
				PHP version $installedVersion is currently installed.\n
				While SilverStripe requires at least PHP version $requiredVersion, upgrading to $recommendedVersion or later is recommended.\n
				If you are installing SilverStripe on a shared web server, please ask your web hosting provider to upgrade PHP for you.";
			$this->error($testDetails);
			return;
		}

		if(version_compare($installedVersion, $recommendedVersion, '<')) {
			$testDetails[2] = "PHP version $installedVersion is currently installed.\n
				Upgrading to at least PHP version $recommendedVersion is recommended.\n
				SilverStripe should run, but you may run into issues. Future releases may require a later version of PHP.\n";
			$this->warning($testDetails);
			return;
		}

		return true;
	}

	/**
	 * Check that a module exists
	 */
	public function checkModuleExists($dirname) {
		$path = $this->getBaseDir() . $dirname;
		return file_exists($path) && ($dirname == 'mysite' || file_exists($path . '/_config.php'));
	}

	/**
	 * The same as {@link requireFile()} but does additional checks
	 * to ensure the module directory is intact.
	 */
	public function requireModule($dirname, $testDetails) {
		$this->testing($testDetails);
		$path = $this->getBaseDir() . $dirname;
		if(!file_exists($path)) {
			$testDetails[2] .= " Directory '$path' not found. Please make sure you have uploaded the SilverStripe files to your webserver correctly.";
			$this->error($testDetails);
		} elseif(!file_exists($path . '/_config.php') && $dirname != 'mysite') {
			$testDetails[2] .= " Directory '$path' exists, but is missing files. Please make sure you have uploaded "
				. "the SilverStripe files to your webserver correctly.";
			$this->error($testDetails);
		}
	}

	public function requireFile($filename, $testDetails) {
		$this->testing($testDetails);
		$filename = $this->getBaseDir() . $filename;
		if(!file_exists($filename)) {
			$testDetails[2] .= " (file '$filename' not found)";
			$this->error($testDetails);
		}
	}

	public function requireWriteable($filename, $testDetails, $absolute = false) {
		$this->testing($testDetails);

		if($absolute) {
			$filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
		} else {
			$filename = $this->getBaseDir() . str_replace('/', DIRECTORY_SEPARATOR, $filename);
		}

		if(file_exists($filename)) $isWriteable = is_writeable($filename);
		else $isWriteable = is_writeable(dirname($filename));

		if(!$isWriteable) {
			if(function_exists('posix_getgroups')) {
				$userID = posix_geteuid();
				$user = posix_getpwuid($userID);

				$currentOwnerID = fileowner(file_exists($filename) ? $filename : dirname($filename));
				$currentOwner = posix_getpwuid($currentOwnerID);

				$testDetails[2] .= "User '$user[name]' needs to be able to write to this file:\n$filename\n\nThe "
					. "file is currently owned by '$currentOwner[name]'.  ";

				if($user['name'] == $currentOwner['name']) {
					$testDetails[2] .= "We recommend that you make the file writeable.";
				} else {

					$groups = posix_getgroups();
					$groupList = array();
					foreach($groups as $group) {
						$groupInfo = posix_getgrgid($group);
						if(in_array($currentOwner['name'], $groupInfo['members'])) $groupList[] = $groupInfo['name'];
					}
					if($groupList) {
						$testDetails[2] .= "	We recommend that you make the file group-writeable "
							. "and change the group to one of these groups:\n - " . implode("\n - ", $groupList)
							. "\n\nFor example:\nchmod g+w $filename\nchgrp " . $groupList[0] . " $filename";
					} else {
						$testDetails[2] .= "  There is no user-group that contains both the web-server user and the "
							. "owner of this file.  Change the ownership of the file, create a new group, or "
							. "temporarily make the file writeable by everyone during the install process.";
					}
				}

			} else {
				$testDetails[2] .= "The webserver user needs to be able to write to this file:\n$filename";
			}

			$this->error($testDetails);
		}
	}

	public function requireTempFolder($testDetails) {
		$this->testing($testDetails);

		try {
			$tempFolder = getTempFolder();
		} catch(Exception $e) {
			$tempFolder = false;
		}

		if(!$tempFolder) {
			$testDetails[2] = "Permission problem gaining access to a temp directory. " .
				"Please create a folder named silverstripe-cache in the base directory " .
				"of the installation and ensure it has the adequate permissions.";
			$this->error($testDetails);
		}
	}

	public function requireApacheModule($moduleName, $testDetails) {
		$this->testing($testDetails);
		if(!in_array($moduleName, apache_get_modules())) {
			$this->error($testDetails);
			return false;
		} else {
			return true;
		}
	}

	public function testApacheRewriteExists($moduleName = 'mod_rewrite') {
		if(function_exists('apache_get_modules') && in_array($moduleName, apache_get_modules())) {
			return true;
		} elseif(isset($_SERVER['HTTP_MOD_REWRITE']) && $_SERVER['HTTP_MOD_REWRITE'] == 'On') {
			return true;
		} else {
			return false;
		}
	}

	public function testIISRewriteModuleExists($moduleName = 'IIS_UrlRewriteModule') {
		if(isset($_SERVER[$moduleName]) && $_SERVER[$moduleName]) {
			return true;
		} else {
			return false;
		}
	}

	public function requireApacheRewriteModule($moduleName, $testDetails) {
		$this->testing($testDetails);
		if($this->testApacheRewriteExists()) {
			return true;
		} else {
			$this->warning($testDetails);
			return false;
		}
	}

	/**
	 * Determines if the web server has any rewriting capability.
	 * @return boolean
	 */
	public function hasRewritingCapability() {
		return ($this->testApacheRewriteExists() || $this->testIISRewriteModuleExists());
	}

	public function requireIISRewriteModule($moduleName, $testDetails) {
		$this->testing($testDetails);
		if($this->testIISRewriteModuleExists()) {
			return true;
		} else {
			$this->warning($testDetails);
			return false;
		}
	}

	public function getDatabaseTypeNice($databaseClass) {
		return substr($databaseClass, 0, -8);
	}

	/**
	 * Get an instance of a helper class for the specific database.
	 * @param string $databaseClass e.g. MySQLDatabase or MSSQLDatabase
	 */
	public function getDatabaseConfigurationHelper($databaseClass) {
		$adapters = DatabaseAdapterRegistry::get_adapters();
		if(isset($adapters[$databaseClass])) {
			$helperPath = $adapters[$databaseClass]['helperPath'];
			$class = str_replace('.php', '', basename($helperPath));
		}
		return (class_exists($class)) ? new $class() : false;
	}

	public function requireDatabaseFunctions($databaseConfig, $testDetails) {
		$this->testing($testDetails);
		$helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
		if (!$helper) {
			$this->error("Couldn't load database helper code for ". $databaseConfig['type']);
			return false;
		}
		$result = $helper->requireDatabaseFunctions($databaseConfig);
		if($result) {
			return true;
		} else {
			$this->error($testDetails);
			return false;
		}
	}

	public function requireDatabaseConnection($databaseConfig, $testDetails) {
		$this->testing($testDetails);
		$helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
		$result = $helper->requireDatabaseConnection($databaseConfig);
		if($result['success']) {
			return true;
		} else {
			$testDetails[2] .= ": " . $result['error'];
			$this->error($testDetails);
			return false;
		}
	}

	public function requireDatabaseVersion($databaseConfig, $testDetails) {
		$this->testing($testDetails);
		$helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
		if(method_exists($helper, 'requireDatabaseVersion')) {
			$result = $helper->requireDatabaseVersion($databaseConfig);
			if($result['success']) {
				return true;
			} else {
				$testDetails[2] .= $result['error'];
				$this->warning($testDetails);
				return false;
			}
		}
		// Skipped test because this database has no required version
		return true;
	}

	public function requireDatabaseServer($databaseConfig, $testDetails) {
		$this->testing($testDetails);
		$helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
		$result = $helper->requireDatabaseServer($databaseConfig);
		if($result['success']) {
			return true;
		} else {
			$testDetails[2] .= ": " . $result['error'];
			$this->error($testDetails);
			return false;
		}
	}

	public function requireDatabaseOrCreatePermissions($databaseConfig, $testDetails) {
		$this->testing($testDetails);
		$helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
		$result = $helper->requireDatabaseOrCreatePermissions($databaseConfig);
		if($result['success']) {
			if($result['alreadyExists']) $testDetails[3] = "Database $databaseConfig[database]";
			else $testDetails[3] = "Able to create a new database";
			$this->testing($testDetails);
			return true;
		} else {
			if(empty($result['cannotCreate'])) {
				$testDetails[2] .= ". Please create the database manually.";
			} else {
				$testDetails[2] .= " (user '$databaseConfig[username]' doesn't have CREATE DATABASE permissions.)";
			}

			$this->error($testDetails);
			return false;
		}
	}

	public function requireDatabaseAlterPermissions($databaseConfig, $testDetails) {
		$this->testing($testDetails);
		$helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
		$result = $helper->requireDatabaseAlterPermissions($databaseConfig);
		if ($result['success']) {
			return true;
		} else {
			$testDetails[2] = "Silverstripe cannot alter tables. This won't prevent installation, however it may "
					. "cause issues if you try to run a /dev/build once installed.";
			$this->warning($testDetails);
			return;
		}
	}

	public function requireServerVariables($varNames, $testDetails) {
		$this->testing($testDetails);
		$missing = array();

		foreach($varNames as $varName) {
			if(!isset($_SERVER[$varName]) || !$_SERVER[$varName]) {
				$missing[] = '$_SERVER[' . $varName . ']';
			}
		}

		if(!$missing) {
			return true;
		} else {
			$testDetails[2] .= " (the following PHP variables are missing: " . implode(", ", $missing) . ")";
			$this->error($testDetails);
		}
	}


	public function requirePostSupport($testDetails) {
		$this->testing($testDetails);

		if(!isset($_POST)) {
			$this->error($testDetails);

			return false;
		}

		return true;
	}

	public function isRunningWebServer($testDetails) {
		$this->testing($testDetails);
		if($testDetails[3]) {
			return true;
		} else {
			$this->warning($testDetails);
			return false;
		}
	}

	// Must be PHP4 compatible
	var $baseDir;

	public function getBaseDir() {
		// Cache the value so that when the installer mucks with SCRIPT_FILENAME half way through, this method
		// still returns the correct value.
		if(!$this->baseDir) $this->baseDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . DIRECTORY_SEPARATOR;
		return $this->baseDir;
	}

	public function testing($testDetails) {
		if(!$testDetails) return;

		$section = $testDetails[0];
		$test = $testDetails[1];

		$message = "OK";
		if(isset($testDetails[3])) $message .= " ($testDetails[3])";

		$this->tests[$section][$test] = array("good", $message);
	}

	public function error($testDetails) {
		$section = $testDetails[0];
		$test = $testDetails[1];

		$this->tests[$section][$test] = array("error", isset($testDetails[2]) ? $testDetails[2] : null);
		$this->errors[] = $testDetails;
	}

	public function warning($testDetails) {
		$section = $testDetails[0];
		$test = $testDetails[1];

		$this->tests[$section][$test] = array("warning", isset($testDetails[2]) ? $testDetails[2] : null);
		$this->warnings[] = $testDetails;
	}

	public function hasErrors() {
		return sizeof($this->errors);
	}

	public function hasWarnings() {
		return sizeof($this->warnings);
	}

}

class Installer extends InstallRequirements {
	public function __construct() {
		// Cache the baseDir value
		$this->getBaseDir();
	}

	public function install($config) {
		?>
		<html>
		<head>
			<meta charset="utf-8"/>
			<title>Installing SilverStripe...</title>
			<link rel="stylesheet" type="text/css" href="<?php echo FRAMEWORK_NAME; ?>/dev/install/css/install.css"/>
			<script src="<?php echo FRAMEWORK_NAME; ?>/thirdparty/jquery/jquery.js"></script>
		</head>
		<body>
		<div class="install-header">
			<div class="inner">
				<div class="brand">
					<span class="logo"></span>

					<h1>SilverStripe</h1>
				</div>
			</div>
		</div>

		<div id="Navigation">&nbsp;</div>
		<div class="clear"><!-- --></div>

		<div class="main">
			<div class="inner">
				<h2>Installing SilverStripe...</h2>

				<p>I am now running through the installation steps (this should take about 30 seconds)</p>

				<p>If you receive a fatal error, refresh this page to continue the installation</p>
				<ul>
<?php

		$webserver = $this->findWebserver();
		$isIIS = $this->isIIS();
		$isApache = $this->isApache();

		flush();

		if(isset($config['stats'])) {
			if(file_exists(FRAMEWORK_NAME . '/silverstripe_version')) {
				$silverstripe_version = file_get_contents(FRAMEWORK_NAME . '/silverstripe_version');
			} else {
				$silverstripe_version = "unknown";
			}

			$phpVersion = urlencode(phpversion());
			$encWebserver = urlencode($webserver);
			$dbType = $config['db']['type'];

			// Try to determine the database version from the helper
			$databaseVersion = $config['db']['type'];
			$helper = $this->getDatabaseConfigurationHelper($dbType);
			if($helper && method_exists($helper, 'getDatabaseVersion')) {
				$versionConfig = $config['db'][$dbType];
				$versionConfig['type'] = $dbType;
				$databaseVersion = urlencode($dbType . ': ' . $helper->getDatabaseVersion($versionConfig));
			}

			$url = "http://ss2stat.silverstripe.com/Installation/add?SilverStripe=$silverstripe_version&PHP=$phpVersion&Database=$databaseVersion&WebServer=$encWebserver";

			if(isset($_SESSION['StatsID']) && $_SESSION['StatsID']) {
				$url .= '&ID=' . $_SESSION['StatsID'];
			}

			@$_SESSION['StatsID'] = file_get_contents($url);
		}

		if(file_exists('mysite/_config.php')) {
			// Truncate the contents of _config instead of deleting it - we can't re-create it because Windows handles permissions slightly
			// differently to UNIX based filesystems - it takes the permissions from the parent directory instead of retaining them
			$fh = fopen('mysite/_config.php', 'wb');
			fclose($fh);
		}

		// Escape user input for safe insertion into PHP file
		$theme = isset($_POST['template']) ? addcslashes($_POST['template'], "\'") : 'simple';
		$locale = isset($_POST['locale']) ? addcslashes($_POST['locale'], "\'") : 'en_US';
		$type = addcslashes($config['db']['type'], "\'");
		$dbConfig = $config['db'][$type];
		$dbConfig = array_map(create_function('$v', 'return addcslashes($v, "\\\'");'), $dbConfig);
		if(!isset($dbConfig['path'])) $dbConfig['path'] = '';
		if(!$dbConfig) {
			echo "<p style=\"color: red\">Bad config submitted</p><pre>";
			print_r($config);
			echo "</pre>";
			die();
		}

		// Write the config file
		global $usingEnv;
		if($usingEnv) {
			$this->statusMessage("Setting up 'mysite/_config.php' for use with _ss_environment.php...");
			$this->writeToFile("mysite/_config.php", <<<PHP
<?php

global \$project;
\$project = 'mysite';

global \$database;
\$database = '{$dbConfig['database']}';

require_once('conf/ConfigureFromEnv.php');

// Set the site locale
i18n::set_locale('$locale');

PHP
			);

		} else {
			$this->statusMessage("Setting up 'mysite/_config.php'...");
			// Create databaseConfig
			$lines = array(
				$lines[] = "\t'type' => '$type'"
			);
			foreach($dbConfig as $key => $value) {
				$lines[] = "\t'{$key}' => '$value'";
			}
			$databaseConfigContent = implode(",\n", $lines);
			$this->writeToFile("mysite/_config.php", <<<PHP
<?php

global \$project;
\$project = 'mysite';

global \$databaseConfig;
\$databaseConfig = array(
{$databaseConfigContent}
);

// Set the site locale
i18n::set_locale('$locale');

PHP
			);
		}

		$this->statusMessage("Setting up 'mysite/_config/config.yml'");
		$this->writeToFile("mysite/_config/config.yml", <<<YML
---
Name: mysite
After:
  - 'framework/*'
  - 'cms/*'
---
# YAML configuration for SilverStripe
# See http://doc.silverstripe.org/framework/en/topics/configuration
# Caution: Indentation through two spaces, not tabs
SSViewer:
  theme: '$theme'
YML
		);

		if(!$this->checkModuleExists('cms')) {
			$this->writeToFile("mysite/code/RootURLController.php", <<<PHP
<?php

class RootURLController extends Controller {

	public function index() {
		echo "<html>Your site is now set up. Start adding controllers to mysite to get started.</html>";
	}

}
PHP
			);
		}

		// Write the appropriate web server configuration file for rewriting support
		if($this->hasRewritingCapability()) {
			if($isApache) {
				$this->statusMessage("Setting up '.htaccess' file...");
				$this->createHtaccess();
			} elseif($isIIS) {
				$this->statusMessage("Setting up 'web.config' file...");
				$this->createWebConfig();
			}
		}

		// Load the SilverStripe runtime
		$_SERVER['SCRIPT_FILENAME'] = dirname(realpath($_SERVER['SCRIPT_FILENAME'])) . '/' . FRAMEWORK_NAME . '/main.php';
		chdir(FRAMEWORK_NAME);

		// Rebuild the manifest
		$_GET['flush'] = true;
		// Show errors as if you're in development mode
		$_SESSION['isDev'] = 1;

		$this->statusMessage("Building database schema...");

		require_once 'core/Core.php';

		// Build database
		$con = new Controller();
		$con->pushCurrent();

		global $databaseConfig;
		DB::connect($databaseConfig);

		$dbAdmin = new DatabaseAdmin();
		$dbAdmin->init();

		$dbAdmin->doBuild(true);

		// Create default administrator user and group in database
		// (not using Security::setDefaultAdmin())
		$adminMember = Security::findAnAdministrator();
		$adminMember->Email = $config['admin']['username'];
		$adminMember->Password = $config['admin']['password'];
		$adminMember->PasswordEncryption = Security::config()->encryption_algorithm;

		try {
			$this->statusMessage('Creating default CMS admin account...');
			$adminMember->write();
		} catch(Exception $e) {
			$this->statusMessage(
				sprintf('Warning: Default CMS admin account could not be created (error: %s)', $e->getMessage())
			);
		}

		// Syncing filesystem (so /assets/Uploads is available instantly, see ticket #2266)
		// show a warning if there was a problem doing so
		try {
			$this->statusMessage('Creating initial filesystem assets...');
			Filesystem::sync();
		} catch(Exception $e) {
			$this->statusMessage(
				sprintf('Warning: Creating initial filesystem assets failed (error: %s)', $e->getMessage())
			);
		}

		$_SESSION['username'] = $config['admin']['username'];
		$_SESSION['password'] = $config['admin']['password'];

		if(!$this->errors) {
			if(isset($_SERVER['HTTP_HOST']) && $this->hasRewritingCapability()) {
				$this->statusMessage("Checking that friendly URLs work...");
				$this->checkRewrite();
			} else {
				require_once 'core/startup/ParameterConfirmationToken.php';
				$token = new ParameterConfirmationToken('flush');
				$params = http_build_query($token->params());

				$destinationURL = 'index.php/' .
					($this->checkModuleExists('cms') ? "home/successfullyinstalled?$params" : "?$params");

				echo <<<HTML
				<li>SilverStripe successfully installed; I am now redirecting you to your SilverStripe site...</li>
				<script>
					setTimeout(function() {
						window.location = "$destinationURL";
					}, 2000);
				</script>
				<noscript>
				<li><a href="$destinationURL">Click here to access your site.</a></li>
				</noscript>
HTML;
			}
		}

		return $this->errors;
	}

	public function writeToFile($filename, $content) {
		$base = $this->getBaseDir();
		$this->statusMessage("Setting up $base$filename");

		if((@$fh = fopen($base . $filename, 'wb')) && fwrite($fh, $content) && fclose($fh)) {
			return true;
		} else {
			$this->error("Couldn't write to file $base$filename");
		}
	}

	public function createHtaccess() {
		$start = "### SILVERSTRIPE START ###\n";
		$end = "\n### SILVERSTRIPE END ###";

		$base = dirname($_SERVER['SCRIPT_NAME']);
		if(defined('DIRECTORY_SEPARATOR')) $base = str_replace(DIRECTORY_SEPARATOR, '/', $base);
		else $base = str_replace("\\", '/', $base);

		if($base != '.') $baseClause = "RewriteBase '$base'\n";
		else $baseClause = "";
		if(strpos(strtolower(php_sapi_name()), "cgi") !== false) $cgiClause = "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n";
		else $cgiClause = "";
		$modulePath = FRAMEWORK_NAME;
		$rewrite = <<<TEXT
# Deny access to templates (but allow from localhost)
<Files *.ss>
	Order deny,allow
	Deny from all
	Allow from 127.0.0.1
</Files>

# Deny access to IIS configuration
<Files web.config>
	Order deny,allow
	Deny from all
</Files>

# Deny access to YAML configuration files which might include sensitive information
<Files *.yml>
	Order allow,deny
	Deny from all
</Files>

# Route errors to static pages automatically generated by SilverStripe
ErrorDocument 404 /assets/error-404.html
ErrorDocument 500 /assets/error-500.html

<IfModule mod_rewrite.c>

	# Turn off index.php handling requests to the homepage fixes issue in apache >=2.4
	<IfModule mod_dir.c>
		DirectoryIndex disabled
	</IfModule>

	SetEnv HTTP_MOD_REWRITE On
	RewriteEngine On
	$baseClause
	$cgiClause

	# Deny access to potentially sensitive files and folders
	RewriteRule ^vendor(/|$) - [F,L,NC]
	RewriteRule silverstripe-cache(/|$) - [F,L,NC]
	RewriteRule composer\.(json|lock) - [F,L,NC]
	
	# Process through SilverStripe if no file with the requested name exists.
	# Pass through the original path as a query parameter, and retain the existing parameters.
	RewriteCond %{REQUEST_URI} ^(.*)$
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule .* $modulePath/main.php?url=%1 [QSA]
</IfModule>
TEXT;

		if(file_exists('.htaccess')) {
			$htaccess = file_get_contents('.htaccess');

			if(strpos($htaccess, '### SILVERSTRIPE START ###') === false && strpos($htaccess, '### SILVERSTRIPE END ###') === false) {
				$htaccess .= "\n### SILVERSTRIPE START ###\n### SILVERSTRIPE END ###\n";
			}

			if(strpos($htaccess, '### SILVERSTRIPE START ###') !== false && strpos($htaccess, '### SILVERSTRIPE END ###') !== false) {
				$start = substr($htaccess, 0, strpos($htaccess, '### SILVERSTRIPE START ###')) . "### SILVERSTRIPE START ###\n";
				$end = "\n" . substr($htaccess, strpos($htaccess, '### SILVERSTRIPE END ###'));
			}
		}

		$this->writeToFile('.htaccess', $start . $rewrite . $end);
	}

	/**
	 * Writes basic configuration to the web.config for IIS
	 * so that rewriting capability can be use.
	 */
	public function createWebConfig() {
		$modulePath = FRAMEWORK_NAME;
		$content = <<<TEXT
<?xml version="1.0" encoding="utf-8"?>
<configuration>
	<system.webServer>
		<security>
			<requestFiltering>
				<hiddenSegments applyToWebDAV="false">
					<add segment="silverstripe-cache" />
					<add segment="vendor" />
					<add segment="composer.json" />
					<add segment="composer.lock" />
				</hiddenSegments>
				<fileExtensions allowUnlisted="true" >
					<add fileExtension=".ss" allowed="false"/>
					<add fileExtension=".yml" allowed="false"/>
				</fileExtensions>
			</requestFiltering>
		</security>
		<rewrite>
			<rules>
				<rule name="SilverStripe Clean URLs" stopProcessing="true">
					<match url="^(.*)$" />
					<conditions>
						<add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
					</conditions>
					<action type="Rewrite" url="$modulePath/main.php?url={R:1}" appendQueryString="true" />
				</rule>
			</rules>
		</rewrite>
	</system.webServer>
</configuration>
TEXT;

		$this->writeToFile('web.config', $content);
	}

	public function checkRewrite() {
		require_once 'core/startup/ParameterConfirmationToken.php';
		$token = new ParameterConfirmationToken('flush');
		$params = http_build_query($token->params());

		$destinationURL = str_replace('install.php', '', $_SERVER['SCRIPT_NAME']) .
			($this->checkModuleExists('cms') ? "home/successfullyinstalled?$params" : "?$params");

		echo <<<HTML
<li id="ModRewriteResult">Testing...</li>
<script>
	if(typeof $ == 'undefined') {
		document.getElemenyById('ModeRewriteResult').innerHTML = "I can't run jQuery ajax to set rewriting; I will redirect you to the homepage to see if everything is working.";
		setTimeout(function() {
			window.location = "$destinationURL";
		}, 10000);
	} else {
		$.ajax({
			method: 'get',
			url: 'InstallerTest/testrewrite',
			complete: function(response) {
				var r = response.responseText.replace(/[^A-Z]?/g,"");
				if(r === "OK") {
					$('#ModRewriteResult').html("Friendly URLs set up successfully; I am now redirecting you to your SilverStripe site...")
					setTimeout(function() {
						window.location = "$destinationURL";
					}, 2000);
				} else {
					$('#ModRewriteResult').html("Friendly URLs are not working. This is most likely because a rewrite module isn't configured "
						+ "correctly on your site. You may need to get your web host or server administrator to do this for you: "
						+ "<ul>"
						+ "<li><strong>mod_rewrite</strong> or other rewrite module is enabled on your web server</li>"
						+ "<li><strong>AllowOverride All</strong> is set for the directory where SilverStripe is installed</li>"
						+ "</ul>");
				}
			}
		});
	}
</script>
<noscript>
	<li><a href="$destinationURL">Click here</a> to check friendly URLs are working. If you get a 404 then something is wrong.</li>
</noscript>
HTML;
	}

	public function var_export_array_nokeys($array) {
		$retval = "array(\n";
		foreach($array as $item) {
			$retval .= "\t'";
			$retval .= trim($item);
			$retval .= "',\n";
		}
		$retval .= ")";
		return $retval;
	}

	/**
	 * Show an installation status message.
	 * The output differs depending on whether this is CLI or web based
	 */
	public function statusMessage($msg) {
		echo "<li>$msg</li>\n";
		flush();
	}
}
