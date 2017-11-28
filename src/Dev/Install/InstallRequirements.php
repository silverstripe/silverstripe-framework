<?php

namespace SilverStripe\Dev\Install;

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SilverStripe\Core\TempFolder;
use SplFileInfo;

/**
 * This class checks requirements
 * Each of the requireXXX functions takes an argument which gives a user description of the test.
 * It's an array of 3 parts:
 *  $description[0] - The test catetgory
 *  $description[1] - The test title
 *  $description[2] - The test error to show, if it goes wrong
 */
class InstallRequirements
{
    /**
     * List of errors
     *
     * @var array
     */
    protected $errors = [];

    /**
     * List of warnings
     *
     * @var array
     */
    protected $warnings = [];

    /**
     * List of tests
     *
     * @var array
     */
    protected $tests = [];

    /**
     * Backup of original ini settings
     * @var array
     */
    protected $originalIni = [];

    /**
     * Base path
     * @var
     */
    protected $baseDir;

    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->baseDir = $basePath;
        } elseif (defined('BASE_PATH')) {
            $this->baseDir = BASE_PATH;
        } else {
            throw new BadMethodCallException("No BASE_PATH defined");
        }
    }

    public function getBaseDir()
    {
        return rtrim($this->baseDir, '/\\') . '/';
    }

    /**
     * Check the database configuration. These are done one after another
     * starting with checking the database function exists in PHP, and
     * continuing onto more difficult checks like database permissions.
     *
     * @param array $databaseConfig The list of database parameters
     * @return boolean Validity of database configuration details
     */
    public function checkDatabase($databaseConfig)
    {
        // Check if support is available
        if (!$this->requireDatabaseFunctions(
            $databaseConfig,
            array(
                "Database Configuration",
                "Database support",
                "Database support in PHP",
                $this->getDatabaseTypeNice($databaseConfig['type'])
            )
        )
        ) {
            return false;
        }

        $path = empty($databaseConfig['path']) ? null : $databaseConfig['path'];
        $server = empty($databaseConfig['server']) ? null : $databaseConfig['server'];

        // Check if the server is available
        $usePath = $path && empty($server);
        if (!$this->requireDatabaseServer(
            $databaseConfig,
            array(
                "Database Configuration",
                "Database server",
                $usePath
                    ? "I couldn't write to path '{$path}'"
                    : "I couldn't find a database server on '{$server}'",
                $usePath
                    ? $path
                    : $server
            )
        )
        ) {
            return false;
        }

        // Check if the connection credentials allow access to the server / database
        if (!$this->requireDatabaseConnection(
            $databaseConfig,
            array(
                "Database Configuration",
                "Database access credentials",
                "That username/password doesn't work"
            )
        )
        ) {
            return false;
        }

        // Check the necessary server version is available
        if (!$this->requireDatabaseVersion(
            $databaseConfig,
            array(
                "Database Configuration",
                "Database server version requirement",
                '',
                'Version ' . $this->getDatabaseConfigurationHelper($databaseConfig['type'])->getDatabaseVersion($databaseConfig)
            )
        )
        ) {
            return false;
        }

        // Check that database creation permissions are available
        if (!$this->requireDatabaseOrCreatePermissions(
            $databaseConfig,
            array(
                "Database Configuration",
                "Can I access/create the database",
                "I can't create new databases and the database '$databaseConfig[database]' doesn't exist"
            )
        )
        ) {
            return false;
        }

        // Check alter permission (necessary to create tables etc)
        if (!$this->requireDatabaseAlterPermissions(
            $databaseConfig,
            array(
                "Database Configuration",
                "Can I ALTER tables",
                "I don't have permission to ALTER tables"
            )
        )
        ) {
            return false;
        }

        // Success!
        return true;
    }

    public function checkAdminConfig($adminConfig)
    {
        if (!$adminConfig['username']) {
            $this->error(array('', 'Please enter a username!'));
        }
        if (!$adminConfig['password']) {
            $this->error(array('', 'Please enter a password!'));
        }
    }

    /**
     * Check if the web server is IIS and version greater than the given version.
     *
     * @param int $fromVersion
     * @return bool
     */
    public function isIIS($fromVersion = 7)
    {
        $webserver = $this->findWebserver();
        if (preg_match('#.*IIS/(?<version>[.\\d]+)$#', $webserver, $matches)) {
            return version_compare($matches['version'], $fromVersion, '>=');
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isApache()
    {
        return strpos($this->findWebserver(), 'Apache') !== false;
    }

    /**
     * Find the webserver software running on the PHP host.
     *
     * @return string|false Server software or boolean FALSE
     */
    public function findWebserver()
    {
        // Try finding from SERVER_SIGNATURE or SERVER_SOFTWARE
        if (!empty($_SERVER['SERVER_SIGNATURE'])) {
            $webserver = $_SERVER['SERVER_SIGNATURE'];
        } elseif (!empty($_SERVER['SERVER_SOFTWARE'])) {
            $webserver = $_SERVER['SERVER_SOFTWARE'];
        } else {
            return false;
        }

        return strip_tags(trim($webserver));
    }

    /**
     * Check everything except the database
     */
    public function check($originalIni)
    {
        $this->originalIni = $originalIni;
        $this->errors = [];
        $isApache = $this->isApache();
        $isIIS = $this->isIIS();
        $webserver = $this->findWebserver();

        $this->requirePHPVersion('5.5.0', '5.5.0', array(
            "PHP Configuration",
            "PHP5 installed",
            null,
            "PHP version " . phpversion()
        ));

        // Check that we can identify the root folder successfully
        $this->requireFile('vendor/silverstripe/framework/src/Dev/Install/config-form.html', array(
            "File permissions",
            "Does the webserver know where files are stored?",
            "The webserver isn't letting me identify where files are stored.",
            $this->getBaseDir()
        ));

        $this->requireModule('mysite', array(
            "File permissions",
            "mysite/ directory exists?",
            ''
        ));
        $this->requireModule('vendor/silverstripe/framework', array(
            "File permissions",
            "vendor/silverstripe/framework/ directory exists?",
            '',
        ));

        $this->requireWriteable('index.php', array("File permissions", "Is the index.php file writeable?", null));

        $this->requireWriteable('.env', ["File permissions", "Is the .env file writeable?", null], false, false);

        if ($isApache) {
            $this->checkApacheVersion(array(
                "Webserver Configuration",
                "Webserver is not Apache 1.x",
                "SilverStripe requires Apache version 2 or greater",
                $webserver
            ));
            $this->requireWriteable('.htaccess', array("File permissions", "Is the .htaccess file writeable?", null));
        } elseif ($isIIS) {
            $this->requireWriteable('web.config', array("File permissions", "Is the web.config file writeable?", null));
        }

        $this->requireWriteable('mysite/_config.php', array(
            "File permissions",
            "Is the mysite/_config.php file writeable?",
            null
        ));

        $this->requireWriteable('mysite/_config/theme.yml', array(
            "File permissions",
            "Is the mysite/_config/theme.yml file writeable?",
            null
        ));

        if (!$this->checkModuleExists('cms')) {
            $this->requireWriteable('mysite/code/RootURLController.php', array(
                "File permissions",
                "Is the mysite/code/RootURLController.php file writeable?",
                null
            ));
        }


        // Ensure root assets dir is writable
        $this->requireWriteable('assets', array("File permissions", "Is the assets/ directory writeable?", null));

        // Ensure all assets files are writable
        $assetsDir = $this->getBaseDir() . 'assets';
        $innerIterator = new RecursiveDirectoryIterator($assetsDir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($innerIterator, RecursiveIteratorIterator::SELF_FIRST);
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), strlen($this->getBaseDir()));
            $message = $file->isDir()
                ? "Is the {$relativePath} directory writeable?"
                : "Is the {$relativePath} file writeable?";
            $this->requireWriteable($relativePath, array("File permissions", $message, null));
        }

        try {
            $tempFolder = TempFolder::getTempFolder($this->getBaseDir());
        } catch (Exception $e) {
            $tempFolder = false;
        }

        $this->requireTempFolder(array('File permissions', 'Is a temporary directory available?', null, $tempFolder));
        if ($tempFolder) {
            // in addition to the temp folder being available, check it is writable
            $this->requireWriteable($tempFolder, array(
                "File permissions",
                sprintf("Is the temporary directory writeable?", $tempFolder),
                null
            ), true);
        }

        // Check for web server, unless we're calling the installer from the command-line
        $this->isRunningWebServer(array("Webserver Configuration", "Server software", "Unknown", $webserver));

        if ($isApache) {
            $this->requireApacheRewriteModule('mod_rewrite', array(
                "Webserver Configuration",
                "URL rewriting support",
                "You need mod_rewrite to use friendly URLs with SilverStripe, but it is not enabled."
            ));
        } elseif ($isIIS) {
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
                "I can't tell whether any rewriting module is running.  You may need to configure a rewriting rule yourself."
            ));
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
        if (!$this->requireFunction("imagecreatetruecolor", array(
            "PHP Configuration",
            "GD2 support",
            "PHP must have GD version 2."
        ))
        ) {
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
            $this->getOriginalIni('date.timezone')
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

        // always_populate_raw_post_data should be set to -1 if PHP < 7.0
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $this->suggestPHPSetting('always_populate_raw_post_data', ['-1'], [
                'PHP Configuration',
                'always_populate_raw_post_data option',
                'It\'s highly recommended to set this to \'-1\' in php 5.x, as $HTTP_RAW_POST_DATA is removed in php 7'
            ]);
        }

        // Check memory allocation
        $this->requireMemory(32 * 1024 * 1024, 64 * 1024 * 1024, array(
            "PHP Configuration",
            "Memory allocation (PHP config option 'memory_limit')",
            "SilverStripe needs a minimum of 32M allocated to PHP, but recommends 64M.",
            $this->getOriginalIni("memory_limit")
        ));

        return $this->errors;
    }

    /**
     * Get ini setting
     *
     * @param string $settingName
     * @return mixed
     */
    protected function getOriginalIni($settingName)
    {
        if (isset($this->originalIni[$settingName])) {
            return $this->originalIni[$settingName];
        }
        return ini_get($settingName);
    }

    public function suggestPHPSetting($settingName, $settingValues, $testDetails)
    {
        $this->testing($testDetails);

        // special case for display_errors, check the original value before
        // it was changed at the start of this script.
        $val = $this->getOriginalIni($settingName);

        if (!in_array($val, $settingValues) && $val != $settingValues) {
            $this->warning($testDetails, "$settingName is set to '$val' in php.ini.  $testDetails[2]");
        }
    }

    public function requirePHPSetting($settingName, $settingValues, $testDetails)
    {
        $this->testing($testDetails);

        $val = $this->getOriginalIni($settingName);
        if (!in_array($val, $settingValues) && $val != $settingValues) {
            $this->error($testDetails, "$settingName is set to '$val' in php.ini.  $testDetails[2]");
        }
    }

    public function suggestClass($class, $testDetails)
    {
        $this->testing($testDetails);

        if (!class_exists($class)) {
            $this->warning($testDetails);
        }
    }

    public function suggestFunction($class, $testDetails)
    {
        $this->testing($testDetails);

        if (!function_exists($class)) {
            $this->warning($testDetails);
        }
    }

    public function requireDateTimezone($testDetails)
    {
        $this->testing($testDetails);
        $val = $this->getOriginalIni('date.timezone');
        $result = $val && in_array($val, timezone_identifiers_list());
        if (!$result) {
            $this->error($testDetails);
        }
    }

    public function requireMemory($min, $recommended, $testDetails)
    {
        $_SESSION['forcemem'] = false;

        $mem = $this->getPHPMemory();
        $memLimit = $this->getOriginalIni("memory_limit");
        if ($mem < (64 * 1024 * 1024)) {
            ini_set('memory_limit', '64M');
            $mem = $this->getPHPMemory();
            $testDetails[3] = $memLimit;
        }

        $this->testing($testDetails);

        if ($mem < $min && $mem > 0) {
            $message = $testDetails[2] . " You only have " . $memLimit . " allocated";
            $this->error($testDetails, $message);
            return false;
        } elseif ($mem < $recommended && $mem > 0) {
            $message = $testDetails[2] . " You only have " . $memLimit . " allocated";
            $this->warning($testDetails, $message);
            return false;
        } elseif ($mem == 0) {
            $message = $testDetails[2] . " We can't determine how much memory you have allocated. "
                . "Install only if you're sure you've allocated at least 20 MB.";
            $this->warning($testDetails, $message);
            return false;
        }
        return true;
    }

    public function getPHPMemory()
    {
        $memString = $this->getOriginalIni("memory_limit");

        switch (strtolower(substr($memString, -1))) {
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


    public function listErrors()
    {
        if ($this->errors) {
            echo "<p>The following problems are preventing me from installing SilverStripe CMS:</p>\n\n";
            foreach ($this->errors as $error) {
                echo "<li>" . htmlentities(implode(", ", $error), ENT_COMPAT, 'UTF-8') . "</li>\n";
            }
        }
    }

    public function showTable($section = null)
    {
        if ($section) {
            $tests = $this->tests[$section];
            $id = strtolower(str_replace(' ', '_', $section));
            echo "<table id=\"{$id}_results\" class=\"testResults\" width=\"100%\">";
            foreach ($tests as $test => $result) {
                echo "<tr class=\"$result[0]\"><td>$test</td><td>"
                    . nl2br(htmlentities($result[1], ENT_COMPAT, 'UTF-8')) . "</td></tr>";
            }
            echo "</table>";
        } else {
            foreach ($this->tests as $section => $tests) {
                $failedRequirements = 0;
                $warningRequirements = 0;

                $output = "";

                foreach ($tests as $test => $result) {
                    if (isset($result['0'])) {
                        switch ($result['0']) {
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

                if ($failedRequirements > 0) {
                    $className = "error";
                    $pluralWarnings = ($warningRequirements == 1) ? 'Warning' : 'Warnings';

                    $text = $failedRequirements . ' Failed and ' . $warningRequirements . ' ' . $pluralWarnings;
                } elseif ($warningRequirements > 0) {
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

    public function requireFunction($funcName, $testDetails)
    {
        $this->testing($testDetails);

        if (!function_exists($funcName)) {
            $this->error($testDetails);
            return false;
        }
        return true;
    }

    public function requireClass($className, $testDetails)
    {
        $this->testing($testDetails);
        if (!class_exists($className)) {
            $this->error($testDetails);
            return false;
        }
        return true;
    }

    /**
     * Require that the given class doesn't exist
     *
     * @param array $classNames
     * @param array $testDetails
     * @return bool
     */
    public function requireNoClasses($classNames, $testDetails)
    {
        $this->testing($testDetails);
        $badClasses = array();
        foreach ($classNames as $className) {
            if (class_exists($className)) {
                $badClasses[] = $className;
            }
        }
        if ($badClasses) {
            $message = $testDetails[2] . ".  The following classes are at fault: " . implode(', ', $badClasses);
            $this->error($testDetails, $message);
            return false;
        }
        return true;
    }

    public function checkApacheVersion($testDetails)
    {
        $this->testing($testDetails);

        $is1pointx = preg_match('#Apache[/ ]1\.#', $testDetails[3]);
        if ($is1pointx) {
            $this->error($testDetails);
        }

        return true;
    }

    public function requirePHPVersion($recommendedVersion, $requiredVersion, $testDetails)
    {
        $this->testing($testDetails);

        $installedVersion = phpversion();

        if (version_compare($installedVersion, $requiredVersion, '<')) {
            $message = "SilverStripe requires PHP version $requiredVersion or later.\n
                PHP version $installedVersion is currently installed.\n
                While SilverStripe requires at least PHP version $requiredVersion, upgrading to $recommendedVersion or later is recommended.\n
                If you are installing SilverStripe on a shared web server, please ask your web hosting provider to upgrade PHP for you.";
            $this->error($testDetails, $message);
            return false;
        }

        if (version_compare($installedVersion, $recommendedVersion, '<')) {
            $message = "PHP version $installedVersion is currently installed.\n
                Upgrading to at least PHP version $recommendedVersion is recommended.\n
                SilverStripe should run, but you may run into issues. Future releases may require a later version of PHP.\n";
            $this->warning($testDetails, $message);
            return false;
        }

        return true;
    }

    /**
     * Check that a module exists
     *
     * @param string $dirname
     * @return bool
     */
    public function checkModuleExists($dirname)
    {
        // Mysite is base-only and doesn't need _config.php to be counted
        if ($dirname === 'mysite') {
            return file_exists($this->getBaseDir() . $dirname);
        }

        $paths = [
            "vendor/silverstripe/{$dirname}/",
            "{$dirname}/",
        ];
        foreach ($paths as $path) {
            $checks = ['_config', '_config.php'];
            foreach ($checks as $check) {
                if (file_exists($this->getBaseDir() . $path . $check)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The same as {@link requireFile()} but does additional checks
     * to ensure the module directory is intact.
     *
     * @param string $dirname
     * @param array $testDetails
     */
    public function requireModule($dirname, $testDetails)
    {
        $this->testing($testDetails);
        $path = $this->getBaseDir() . $dirname;
        if (!file_exists($path)) {
            $testDetails[2] .= " Directory '$path' not found. Please make sure you have uploaded the SilverStripe files to your webserver correctly.";
            $this->error($testDetails);
        } elseif (!file_exists($path . '/_config.php') && $dirname != 'mysite') {
            $testDetails[2] .= " Directory '$path' exists, but is missing files. Please make sure you have uploaded "
                . "the SilverStripe files to your webserver correctly.";
            $this->error($testDetails);
        }
    }

    public function requireFile($filename, $testDetails)
    {
        $this->testing($testDetails);
        $filename = $this->getBaseDir() . $filename;
        if (!file_exists($filename)) {
            $testDetails[2] .= " (file '$filename' not found)";
            $this->error($testDetails);
        }
    }

    public function requireWriteable($filename, $testDetails, $absolute = false, $error = true)
    {
        $this->testing($testDetails);

        if ($absolute) {
            $filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);
        } else {
            $filename = $this->getBaseDir() . str_replace('/', DIRECTORY_SEPARATOR, $filename);
        }

        if (file_exists($filename)) {
            $isWriteable = is_writeable($filename);
        } else {
            $isWriteable = is_writeable(dirname($filename));
        }

        if (!$isWriteable) {
            if (function_exists('posix_getgroups')) {
                $userID = posix_geteuid();
                $user = posix_getpwuid($userID);

                $currentOwnerID = fileowner(file_exists($filename) ? $filename : dirname($filename));
                $currentOwner = posix_getpwuid($currentOwnerID);

                $testDetails[2] .= "User '$user[name]' needs to be able to write to this file:\n$filename\n\nThe "
                    . "file is currently owned by '$currentOwner[name]'.  ";

                if ($user['name'] == $currentOwner['name']) {
                    $testDetails[2] .= "We recommend that you make the file writeable.";
                } else {
                    $groups = posix_getgroups();
                    $groupList = array();
                    foreach ($groups as $group) {
                        $groupInfo = posix_getgrgid($group);
                        if (in_array($currentOwner['name'], $groupInfo['members'])) {
                            $groupList[] = $groupInfo['name'];
                        }
                    }
                    if ($groupList) {
                        $testDetails[2] .= "    We recommend that you make the file group-writeable "
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

            if ($error) {
                $this->error($testDetails);
            } else {
                $this->warning($testDetails);
            }
        }
    }

    public function requireTempFolder($testDetails)
    {
        $this->testing($testDetails);

        try {
            $tempFolder = TempFolder::getTempFolder($this->getBaseDir());
        } catch (Exception $e) {
            $tempFolder = false;
        }

        if (!$tempFolder) {
            $testDetails[2] = "Permission problem gaining access to a temp directory. " .
                "Please create a folder named silverstripe-cache in the base directory " .
                "of the installation and ensure it has the adequate permissions.";
            $this->error($testDetails);
        }
    }

    public function requireApacheModule($moduleName, $testDetails)
    {
        $this->testing($testDetails);
        if (!in_array($moduleName, apache_get_modules())) {
            $this->error($testDetails);
            return false;
        } else {
            return true;
        }
    }

    public function testApacheRewriteExists($moduleName = 'mod_rewrite')
    {
        if (function_exists('apache_get_modules') && in_array($moduleName, apache_get_modules())) {
            return true;
        }
        if (isset($_SERVER['HTTP_MOD_REWRITE']) && $_SERVER['HTTP_MOD_REWRITE'] == 'On') {
            return true;
        }
        if (isset($_SERVER['REDIRECT_HTTP_MOD_REWRITE']) && $_SERVER['REDIRECT_HTTP_MOD_REWRITE'] == 'On') {
            return true;
        }
        return false;
    }

    public function testIISRewriteModuleExists($moduleName = 'IIS_UrlRewriteModule')
    {
        if (isset($_SERVER[$moduleName]) && $_SERVER[$moduleName]) {
            return true;
        } else {
            return false;
        }
    }

    public function requireApacheRewriteModule($moduleName, $testDetails)
    {
        $this->testing($testDetails);
        if ($this->testApacheRewriteExists()) {
            return true;
        } else {
            $this->error($testDetails);
            return false;
        }
    }

    /**
     * Determines if the web server has any rewriting capability.
     * @return boolean
     */
    public function hasRewritingCapability()
    {
        return ($this->testApacheRewriteExists() || $this->testIISRewriteModuleExists());
    }

    public function requireIISRewriteModule($moduleName, $testDetails)
    {
        $this->testing($testDetails);
        if ($this->testIISRewriteModuleExists()) {
            return true;
        } else {
            $this->warning($testDetails);
            return false;
        }
    }

    public function getDatabaseTypeNice($databaseClass)
    {
        return substr($databaseClass, 0, -8);
    }

    /**
     * Get an instance of a helper class for the specific database.
     *
     * @param string $databaseClass e.g. MySQLDatabase or MSSQLDatabase
     * @return DatabaseConfigurationHelper
     */
    public function getDatabaseConfigurationHelper($databaseClass)
    {
        return DatabaseAdapterRegistry::getDatabaseConfigurationHelper($databaseClass);
    }

    public function requireDatabaseFunctions($databaseConfig, $testDetails)
    {
        $this->testing($testDetails);
        $helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
        if (!$helper) {
            $this->error($testDetails, "Couldn't load database helper code for " . $databaseConfig['type']);
            return false;
        }
        $result = $helper->requireDatabaseFunctions($databaseConfig);
        if ($result) {
            return true;
        } else {
            $this->error($testDetails);
            return false;
        }
    }

    public function requireDatabaseConnection($databaseConfig, $testDetails)
    {
        $this->testing($testDetails);
        $helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
        $result = $helper->requireDatabaseConnection($databaseConfig);
        if ($result['success']) {
            return true;
        } else {
            $testDetails[2] .= ": " . $result['error'];
            $this->error($testDetails);
            return false;
        }
    }

    public function requireDatabaseVersion($databaseConfig, $testDetails)
    {
        $this->testing($testDetails);
        $helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
        if (method_exists($helper, 'requireDatabaseVersion')) {
            $result = $helper->requireDatabaseVersion($databaseConfig);
            if ($result['success']) {
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

    public function requireDatabaseServer($databaseConfig, $testDetails)
    {
        $this->testing($testDetails);
        $helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
        $result = $helper->requireDatabaseServer($databaseConfig);
        if ($result['success']) {
            return true;
        } else {
            $message = $testDetails[2] . ": " . $result['error'];
            $this->error($testDetails, $message);
            return false;
        }
    }

    public function requireDatabaseOrCreatePermissions($databaseConfig, $testDetails)
    {
        $this->testing($testDetails);
        $helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
        $result = $helper->requireDatabaseOrCreatePermissions($databaseConfig);
        if ($result['success']) {
            if ($result['alreadyExists']) {
                $testDetails[3] = "Database $databaseConfig[database]";
            } else {
                $testDetails[3] = "Able to create a new database";
            }
            $this->testing($testDetails);
            return true;
        } else {
            if (empty($result['cannotCreate'])) {
                $message = $testDetails[2] . ". Please create the database manually.";
            } else {
                $message = $testDetails[2] . " (user '$databaseConfig[username]' doesn't have CREATE DATABASE permissions.)";
            }

            $this->error($testDetails, $message);
            return false;
        }
    }

    public function requireDatabaseAlterPermissions($databaseConfig, $testDetails)
    {
        $this->testing($testDetails);
        $helper = $this->getDatabaseConfigurationHelper($databaseConfig['type']);
        $result = $helper->requireDatabaseAlterPermissions($databaseConfig);
        if ($result['success']) {
            return true;
        } else {
            $message = "Silverstripe cannot alter tables. This won't prevent installation, however it may "
                . "cause issues if you try to run a /dev/build once installed.";
            $this->warning($testDetails, $message);
            return false;
        }
    }

    public function requireServerVariables($varNames, $testDetails)
    {
        $this->testing($testDetails);
        $missing = array();

        foreach ($varNames as $varName) {
            if (!isset($_SERVER[$varName]) || !$_SERVER[$varName]) {
                $missing[] = '$_SERVER[' . $varName . ']';
            }
        }

        if (!$missing) {
            return true;
        }

        $message = $testDetails[2] . " (the following PHP variables are missing: " . implode(", ", $missing) . ")";
        $this->error($testDetails, $message);
        return false;
    }


    public function requirePostSupport($testDetails)
    {
        $this->testing($testDetails);

        if (!isset($_POST)) {
            $this->error($testDetails);

            return false;
        }

        return true;
    }

    public function isRunningWebServer($testDetails)
    {
        $this->testing($testDetails);
        if ($testDetails[3]) {
            return true;
        } else {
            $this->warning($testDetails);
            return false;
        }
    }

    public function testing($testDetails)
    {
        if (!$testDetails) {
            return;
        }

        $section = $testDetails[0];
        $test = $testDetails[1];

        $message = "OK";
        if (isset($testDetails[3])) {
            $message .= " ($testDetails[3])";
        }

        $this->tests[$section][$test] = array("good", $message);
    }

    public function error($testDetails, $message = null)
    {
        if (!is_array($testDetails)) {
            throw new InvalidArgumentException("Invalid error");
        }
        $section = $testDetails[0];
        $test = $testDetails[1];
        if (!$message && isset($testDetails[2])) {
            $message = $testDetails[2];
        }

        $this->tests[$section][$test] = array("error", $message);
        $this->errors[] = $testDetails;
    }

    public function warning($testDetails, $message = null)
    {
        if (!is_array($testDetails)) {
            throw new InvalidArgumentException("Invalid warning");
        }
        $section = $testDetails[0];
        $test = $testDetails[1];
        if (!$message && isset($testDetails[2])) {
            $message = $testDetails[2];
        }

        $this->tests[$section][$test] = array("warning", $message);
        $this->warnings[] = $testDetails;
    }

    public function hasErrors()
    {
        return sizeof($this->errors);
    }

    public function hasWarnings()
    {
        return sizeof($this->warnings);
    }
}
