<?php

namespace SilverStripe\Dev\Install;

use Exception;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Control\HTTPApplication;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Startup\ParameterConfirmationToken;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\Security;

/**
 * SilverStripe CMS SilverStripe\Dev\Install\Installer
 * This installer doesn't use any of the fancy SilverStripe stuff in case it's unsupported.
 */
class Installer extends InstallRequirements
{
    public function __construct()
    {
        // Cache the baseDir value
        $this->getBaseDir();
    }

    protected function installHeader()
    {
        ?>
        <html>
        <head>
            <meta charset="utf-8"/>
            <title>Installing SilverStripe...</title>
            <link rel="stylesheet" type="text/css"
                  href="framework/src/Dev/Install/client/styles/install.css"/>
            <script src="//code.jquery.com/jquery-1.7.2.min.js"></script>
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
    }

    public function install($config)
    {
        // Render header
        $this->installHeader();

        $webserver = $this->findWebserver();
        $isIIS = $this->isIIS();
        $isApache = $this->isApache();

        flush();

        if (isset($config['stats'])) {
            if (file_exists(FRAMEWORK_PATH . '/silverstripe_version')) {
                $silverstripe_version = file_get_contents(FRAMEWORK_PATH . '/silverstripe_version');
            } else {
                $silverstripe_version = "unknown";
            }

            $phpVersion = urlencode(phpversion());
            $encWebserver = urlencode($webserver);
            $dbType = $config['db']['type'];

            // Try to determine the database version from the helper
            $databaseVersion = $config['db']['type'];
            $helper = $this->getDatabaseConfigurationHelper($dbType);
            if ($helper && method_exists($helper, 'getDatabaseVersion')) {
                $versionConfig = $config['db'][$dbType];
                $versionConfig['type'] = $dbType;
                $databaseVersion = urlencode($dbType . ': ' . $helper->getDatabaseVersion($versionConfig));
            }

            $url = "http://ss2stat.silverstripe.com/Installation/add?SilverStripe=$silverstripe_version&PHP=$phpVersion&Database=$databaseVersion&WebServer=$encWebserver";

            if (isset($_SESSION['StatsID']) && $_SESSION['StatsID']) {
                $url .= '&ID=' . $_SESSION['StatsID'];
            }

            @$_SESSION['StatsID'] = file_get_contents($url);
        }

        if (file_exists('mysite/_config.php')) {
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
        foreach ($dbConfig as &$configValue) {
            $configValue = addcslashes($configValue, "\\\'");
        }
        if (!isset($dbConfig['path'])) {
            $dbConfig['path'] = '';
        }
        if (!$dbConfig) {
            echo "<p style=\"color: red\">Bad config submitted</p><pre>";
            print_r($config);
            echo "</pre>";
            die();
        }

        // Write the config file
        global $usingEnv;
        if ($usingEnv) {
            $this->statusMessage("Setting up 'mysite/_config.php' for use with environment variables...");
            $this->writeToFile("mysite/_config.php", "<?php\n ");
        } else {
            $this->statusMessage("Setting up 'mysite/_config.php'...");
            // Create databaseConfig
            $lines = array(
                $lines[] = "    'type' => '$type'"
            );
            foreach ($dbConfig as $key => $value) {
                $lines[] = "    '{$key}' => '$value'";
            }
            $databaseConfigContent = implode(",\n", $lines);
            $this->writeToFile("mysite/_config.php", <<<PHP
<?php

use SilverStripe\\ORM\\DB;

DB::setConfig([
{$databaseConfigContent}
]);

PHP
            );
        }

        $this->statusMessage("Setting up 'mysite/_config/config.yml'");
        $this->writeToFile("mysite/_config/config.yml", <<<YML
---
Name: mysite
---
# YAML configuration for SilverStripe
# See http://doc.silverstripe.org/framework/en/topics/configuration
# Caution: Indentation through two spaces, not tabs
SilverStripe\\View\\SSViewer:
  themes:
    - '$theme'
    - '\$default'
SilverStripe\\i18n\\i18n:
  default_locale: '$locale'
YML
        );

        if (!$this->checkModuleExists('cms')) {
            $this->writeToFile("mysite/code/RootURLController.php", <<<PHP
<?php

use SilverStripe\\Control\\Controller;

class RootURLController extends Controller {

    public function index() {
        echo "<html>Your site is now set up. Start adding controllers to mysite to get started.</html>";
    }

}
PHP
            );
        }

        // Write the appropriate web server configuration file for rewriting support
        if ($this->hasRewritingCapability()) {
            if ($isApache) {
                $this->statusMessage("Setting up '.htaccess' file...");
                $this->createHtaccess();
            } elseif ($isIIS) {
                $this->statusMessage("Setting up 'web.config' file...");
                $this->createWebConfig();
            }
        }

        // Mock request
        $session = new Session(isset($_SESSION) ? $_SESSION : array());
        $request = new HTTPRequest('GET', '/');
        $request->setSession($session);

        // Install kernel (fix to dev)
        $kernel = new CoreKernel(BASE_PATH);
        $kernel->setEnvironment(Kernel::DEV);
        $app = new HTTPApplication($kernel);

        // Build db within HTTPApplication
        $app->execute($request, function (HTTPRequest $request) use ($config) {
            // Start session and execute
            $request->getSession()->init();

            // Output status
            $this->statusMessage("Building database schema...");

            // Setup DB
            $dbAdmin = new DatabaseAdmin();
            $dbAdmin->setRequest($request);
            $dbAdmin->pushCurrent();
            $dbAdmin->doInit();
            $dbAdmin->doBuild(true);

            // Create default administrator user and group in database
            // (not using Security::setDefaultAdmin())
            $adminMember = DefaultAdminService::singleton()->findOrCreateDefaultAdmin();
            $adminMember->Email = $config['admin']['username'];
            $adminMember->Password = $config['admin']['password'];
            $adminMember->PasswordEncryption = Security::config()->get('encryption_algorithm');

            try {
                $this->statusMessage('Creating default CMS admin account...');
                $adminMember->write();
            } catch (Exception $e) {
                $this->statusMessage(
                    sprintf('Warning: Default CMS admin account could not be created (error: %s)', $e->getMessage())
                );
            }

            $request->getSession()->set('username', $config['admin']['username']);
            $request->getSession()->set('password', $config['admin']['password']);
            $request->getSession()->save();
        }, true);

        // Check result of install
        if (!$this->errors) {
            if (isset($_SERVER['HTTP_HOST']) && $this->hasRewritingCapability()) {
                $this->statusMessage("Checking that friendly URLs work...");
                $this->checkRewrite();
            } else {
                $token = new ParameterConfirmationToken('flush', $request);
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

    public function writeToFile($filename, $content)
    {
        $base = $this->getBaseDir();
        $this->statusMessage("Setting up $base$filename");

        if ((@$fh = fopen($base . $filename, 'wb')) && fwrite($fh, $content) && fclose($fh)) {
            return true;
        }
        $this->error("Couldn't write to file $base$filename");
        return false;
    }

    public function createHtaccess()
    {
        $start = "### SILVERSTRIPE START ###\n";
        $end = "\n### SILVERSTRIPE END ###";

        $base = dirname($_SERVER['SCRIPT_NAME']);
        if (defined('DIRECTORY_SEPARATOR')) {
            $base = str_replace(DIRECTORY_SEPARATOR, '/', $base);
        } else {
            $base = str_replace("\\", '/', $base);
        }

        if ($base != '.') {
            $baseClause = "RewriteBase '$base'\n";
        } else {
            $baseClause = "";
        }
        if (strpos(strtolower(php_sapi_name()), "cgi") !== false) {
            $cgiClause = "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n";
        } else {
            $cgiClause = "";
        }
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
    RewriteRule .* framework/main.php?url=%1 [QSA]
</IfModule>
TEXT;

        if (file_exists('.htaccess')) {
            $htaccess = file_get_contents('.htaccess');

            if (strpos($htaccess, '### SILVERSTRIPE START ###') === false
                && strpos($htaccess, '### SILVERSTRIPE END ###') === false
            ) {
                $htaccess .= "\n### SILVERSTRIPE START ###\n### SILVERSTRIPE END ###\n";
            }

            if (strpos($htaccess, '### SILVERSTRIPE START ###') !== false
                && strpos($htaccess, '### SILVERSTRIPE END ###') !== false
            ) {
                $start = substr($htaccess, 0, strpos($htaccess, '### SILVERSTRIPE START ###'))
                    . "### SILVERSTRIPE START ###\n";
                $end = "\n" . substr($htaccess, strpos($htaccess, '### SILVERSTRIPE END ###'));
            }
        }

        $this->writeToFile('.htaccess', $start . $rewrite . $end);
    }

    /**
     * Writes basic configuration to the web.config for IIS
     * so that rewriting capability can be use.
     */
    public function createWebConfig()
    {
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
                    <action type="Rewrite" url="framework/main.php?url={R:1}" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
TEXT;

        $this->writeToFile('web.config', $content);
    }

    public function checkRewrite()
    {
        $token = new ParameterConfirmationToken('flush', new HTTPRequest('GET', '/'));
        $params = http_build_query($token->params());

        $destinationURL = str_replace('install.php', '', $_SERVER['SCRIPT_NAME']) .
            ($this->checkModuleExists('cms') ? "home/successfullyinstalled?$params" : "?$params");

        echo <<<HTML
<li id="ModRewriteResult">Testing...</li>
<script>
    if (typeof $ == 'undefined') {
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
                if (r === "OK") {
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

    public function var_export_array_nokeys($array)
    {
        $retval = "array(\n";
        foreach ($array as $item) {
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
     *
     * @param string $msg
     */
    public function statusMessage($msg)
    {
        echo "<li>$msg</li>\n";
        flush();
    }
}
