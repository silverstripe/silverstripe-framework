<?php

namespace SilverStripe\Dev\Install;

use Exception;
use SilverStripe\Control\Cookie;
use SilverStripe\Control\HTTPApplication;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPRequestBuilder;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\EnvironmentLoader;
use SilverStripe\Core\Kernel;
use SilverStripe\Core\Startup\ParameterConfirmationToken;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\Security;

/**
 * This installer doesn't use any of the fancy SilverStripe stuff in case it's unsupported.
 */
class Installer extends InstallRequirements
{
    /**
     * value='' attribute placeholder for password fields
     */
    const PASSWORD_PLACEHOLDER = '********';

    protected function installHeader()
    {
        ?>
        <html>
        <head>
            <meta charset="utf-8"/>
            <title>Installing SilverStripe...</title>
            <link rel="stylesheet" type="text/css"
                  href="resources/silverstripe/framework/src/Dev/Install/client/styles/install.css"/>
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

        $isIIS = $this->isIIS();
        $isApache = $this->isApache();

        flush();

        // Send install stats
        if (!empty($config['stats'])) {
            $this->sendInstallStats($config);
        }

        // Cleanup _config.php
        if (file_exists('mysite/_config.php')) {
            // Truncate the contents of _config instead of deleting it - we can't re-create it because Windows handles permissions slightly
            // differently to UNIX based filesystems - it takes the permissions from the parent directory instead of retaining them
            $fh = fopen('mysite/_config.php', 'wb');
            fclose($fh);
        }

        // Write all files
        $this->writeIndexPHP();
        $this->writeConfigPHP($config);
        $this->writeConfigYaml($config);
        $this->writeConfigEnv($config);

        // Write other stuff
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
                $this->createHtaccess();
            } elseif ($isIIS) {
                $this->createWebConfig();
            }
        }

        // Build request
        $request = HTTPRequestBuilder::createFromEnvironment();

        // Install kernel (fix to dev)
        $kernel = new CoreKernel(BASE_PATH);
        $kernel->setEnvironment(Kernel::DEV);
        $app = new HTTPApplication($kernel);

        // Build db within HTTPApplication
        $app->execute($request, function (HTTPRequest $request) use ($config) {
            // Suppress cookie errors on install
            Cookie::config()->set('report_errors', false);

            // Start session and execute
            $request->getSession()->init($request);

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
            $username = $config['admin']['username'];
            $password = $config['admin']['password'];
            $adminMember = DefaultAdminService::singleton()
                ->findOrCreateAdmin(
                    $username,
                    _t('SilverStripe\\Security\\DefaultAdminService.DefaultAdminFirstname', 'Default Admin')
                );
            $adminMember->Email = $username;
            $adminMember->Password = $password;
            $adminMember->PasswordEncryption = Security::config()->get('encryption_algorithm');

            try {
                $this->statusMessage('Creating default CMS admin account...');
                $adminMember->write();
            } catch (Exception $e) {
                $this->statusMessage(
                    sprintf('Warning: Default CMS admin account could not be created (error: %s)', $e->getMessage())
                );
            }

            $request->getSession()->set('username', $username);
            $request->getSession()->set('password', $password);
            $request->getSession()->save($request);
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

    protected function writeIndexPHP()
    {
        $content = <<<'PHP'
<?php

use SilverStripe\Control\HTTPApplication;
use SilverStripe\Control\HTTPRequestBuilder;
use SilverStripe\Core\CoreKernel;
use SilverStripe\Core\Startup\ErrorControlChainMiddleware;

require __DIR__ . '/vendor/autoload.php';

// Build request and detect flush
$request = HTTPRequestBuilder::createFromEnvironment();

// Default application
$kernel = new CoreKernel(BASE_PATH);
$app = new HTTPApplication($kernel);
$app->addMiddleware(new ErrorControlChainMiddleware($app));
$response = $app->handle($request);
$response->output();
PHP;
        $this->writeToFile('index.php', $content);
    }

    /**
     * Write all .env files
     *
     * @param $config
     */
    protected function writeConfigEnv($config)
    {
        if (!$config['usingEnv']) {
            return;
        }

        $path = $this->getBaseDir() . '.env';
        $vars = [];

        // Retain existing vars
        $env = new EnvironmentLoader();
        if (file_exists($path)) {
            $vars = $env->loadFile($path) ?: [];
        }

        // Set base URL
        if (!isset($vars['SS_BASE_URL']) && isset($_SERVER['HTTP_HOST'])) {
            $vars['SS_BASE_URL'] = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL;
        }

        // Set DB env
        if (empty($config['db']['database'])) {
            $vars['SS_DATABASE_CHOOSE_NAME'] = true;
        } else {
            $vars['SS_DATABASE_NAME'] = $config['db']['database'];
        }
        $vars['SS_DATABASE_CLASS'] = $config['db']['type'];
        if (isset($config['db']['server'])) {
            $vars['SS_DATABASE_SERVER'] = $config['db']['server'];
        }
        if (isset($config['db']['username'])) {
            $vars['SS_DATABASE_USERNAME'] = $config['db']['username'];
        }
        if (isset($config['db']['password'])) {
            $vars['SS_DATABASE_PASSWORD'] = $config['db']['password'];
        }
        if (isset($config['db']['path'])) {
            $vars['SS_DATABASE_PATH'] = $config['db']['path'];
            // sqlite compat
            $vars['SS_SQLITE_DATABASE_PATH'] = $config['db']['path'];
        }
        if (isset($config['db']['key'])) {
            $vars['SS_DATABASE_KEY'] = $config['db']['key'];
            // sqlite compat
            $vars['SS_SQLITE_DATABASE_KEY'] = $config['db']['key'];
        }

        // Write all env vars
        $lines = [
            '# Generated by SilverStripe Installer'
        ];
        ksort($vars);
        foreach ($vars as $key => $value) {
            $lines[] = $key . '="' . addcslashes($value, '"') . '"';
        }

        $this->writeToFile('.env', implode("\n", $lines));

        // Re-load env vars for installer access
        $env->loadFile($path);
    }

    /**
     * Write all *.php files
     *
     * @param array $config
     */
    protected function writeConfigPHP($config)
    {
        if ($config['usingEnv']) {
            $this->writeToFile("mysite/_config.php", "<?php\n ");
            return;
        }

        // Create databaseConfig
        $lines = [];
        foreach ($config['db'] as $key => $value) {
            $lines[] = sprintf(
                "    '%s' => '%s'",
                addslashes($key),
                addslashes($value)
            );
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

    /**
     * Write yml files
     *
     * @param array $config
     */
    protected function writeConfigYaml($config)
    {
        // Escape user input for safe insertion into PHP file
        $locale = $this->ymlString($config['locale']);

        // Set either specified, or no theme
        if ($config['theme'] && $config['theme'] !== 'tutorial') {
            $theme = $this->ymlString($config['theme']);
            $themeYML = <<<YML
    - '$theme'
    - '\$default'
YML;
        } else {
            $themeYML = <<<YML
    - '\$default'
YML;
        }

        // Write theme.yml
        $this->writeToFile("mysite/_config/theme.yml", <<<YML
---
Name: mytheme
---
SilverStripe\\View\\SSViewer:
  themes:
$themeYML
SilverStripe\\i18n\\i18n:
  default_locale: '$locale'
YML
        );
    }

    /**
     * Escape yml string
     *
     * @param string $string
     * @return mixed
     */
    protected function ymlString($string)
    {
        // just escape single quotes using ''
        return str_replace("'", "''", $string);
    }

    /**
     * Write file to given location
     *
     * @param $filename
     * @param $content
     * @return bool
     */
    public function writeToFile($filename, $content)
    {
        $base = $this->getBaseDir();
        $this->statusMessage("Setting up $base$filename");

        if ((@$fh = fopen($base . $filename, 'wb')) && fwrite($fh, $content) && fclose($fh)) {
            // Set permissions to writable
            @chmod($base . $filename, 0775);
            return true;
        }
        $this->error("Couldn't write to file $base$filename");
        return false;
    }

    /**
     * Ensure root .htaccess is setup
     */
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
<Files ~ "\.ya?ml$">
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
        DirectorySlash On
    </IfModule>

    SetEnv HTTP_MOD_REWRITE On
    RewriteEngine On
    $baseClause
    $cgiClause

    # Deny access to potentially sensitive files and folders
    RewriteRule ^vendor(/|$) - [F,L,NC]
    RewriteRule ^\.env - [F,L,NC]
    RewriteRule silverstripe-cache(/|$) - [F,L,NC]
    RewriteRule composer\.(json|lock) - [F,L,NC]
    RewriteRule (error|silverstripe|debug)\.log - [F,L,NC]

    # Process through SilverStripe if no file with the requested name exists.
    # Pass through the original path as a query parameter, and retain the existing parameters.
    # Try finding framework in the vendor folder first
    RewriteCond %{REQUEST_URI} ^(.*)$
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule .* index.php
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
                    <action type="Rewrite" url="index.php" appendQueryString="true" />
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

    /**
     * @param $config
     */
    protected function sendInstallStats($config)
    {
        // Try to determine the database version from the helper
        $dbType = $config['db']['type'];
        $helper = $this->getDatabaseConfigurationHelper($dbType);
        if ($helper) {
            $databaseVersion = $dbType . ': ' . $helper->getDatabaseVersion($config['db']);
        } else {
            $databaseVersion = $dbType;
        }

        $args = http_build_query(array_filter([
            'SilverStripe' => $config['version'],
            'PHP' => phpversion(),
            'Database' => $databaseVersion,
            'WebServer' => $this->findWebserver(),
            'ID' => empty($_SESSION['StatsID']) ? null : $_SESSION['StatsID']
        ]));
        $url = "http://ss2stat.silverstripe.com/Installation/add?{$args}";
        @$_SESSION['StatsID'] = file_get_contents($url);
    }
}
