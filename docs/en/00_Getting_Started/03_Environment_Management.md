# Environment management

As part of website development and hosting it is natural for our sites to be hosted on several different environments.
These can be our laptops for local development, a testing server for customers to test changes on, or a production 
server.

For each of these environments we may require slightly different configurations for our servers. This could be our debug
level, caching backends, or - of course - sensitive information such as database credentials.

To manage environment variables, as well as other server globals, the [api:SilverStripe\Core\Environment] class
provides a set of APIs and helpers.

## Security considerations

Sensitive credentials should not be stored in a VCS or project code and should only be stored on the environment in 
question. When using live environments the use of `.env` files is discouraged and instead one should use "first class"
environment variables.

If you do use a `.env` file on your servers, you must ensure that external access to `.env` files is blocked by the
webserver.

## Managing environment variables with `.env` files

By default a file named `.env` must be placed in your project root (ie: the same folder as your `composer.json`) or the parent
directory. If this file exists, it will be automatically loaded by the framework and the environment variables will be
set. An example `.env` file is included in the default installer named `.env.example`.

**Note:** The file must be named exactly `.env` and not any variation (such as `mysite.env` or `.env.mysite`) or it will not be detected automatically. If you wish to load environment variables from a file with a different name, you will need to do so manually. See the [Including an extra `.env` file](#including-an-extra-env-file) section below for more information.

## Managing environment variables with Apache

You can set "real" environment variables using Apache. Please 
[see the Apache docs for more information](https://httpd.apache.org/docs/current/env.html)

## How to access the environment variables

Accessing the environment varaibles should be done via the `Environment::getEnv()` method

```php
use SilverStripe\Core\Environment;
Environment::getEnv('SS_DATABASE_CLASS');
```

Individual settings can be assigned via `Environment::setEnv()` or `Environment::putEnv()` methods.

```php
use SilverStripe\Core\Environment;
Environment::setEnv('API_KEY', 'AABBCCDDEEFF012345');
```

## Including an extra `.env` file

Sometimes it may be useful to include an extra `.env` file - on a shared local development environment where all
database credentials could be the same. To do this, you can add this snippet to your `app/_config.php` file:

Note that by default variables cannot be overloaded from this file; Existing values will be preferred
over values in this file.

```php
use SilverStripe\Core\EnvironmentLoader;
$env = BASE_PATH . '/app/.env';
$loader = new EnvironmentLoader();
$loader->loadFile($env);
```

## Core environment variables

SilverStripe core environment variables are listed here, though you're free to define any you need for your application.

| Name  | Description |
| ----  | ----------- |
| `SS_DATABASE_CLASS` | The database class to use, MySQLPDODatabase, MySQLDatabase, MSSQLDatabase, etc. defaults to MySQLPDODatabase.|
| `SS_DATABASE_SERVER`| The database server to use, defaulting to localhost.|
| `SS_DATABASE_USERNAME`| The database username (mandatory).|
| `SS_DATABASE_PASSWORD`| The database password (mandatory).|
| `SS_DATABASE_PORT`|     The database port.|
| `SS_DATABASE_SUFFIX`|   A suffix to add to the database name.|
| `SS_DATABASE_PREFIX`|   A prefix to add to the database name.|
| `SS_DATABASE_TIMEZONE`| Set the database timezone to something other than the system timezone.
| `SS_DATABASE_NAME` | Set the database name. Assumes the `$database` global variable in your config is missing or empty. |
| `SS_DATABASE_CHOOSE_NAME`| Boolean/Int.  If defined, then the system will choose a default database name for you if one isn't give in the $database variable.  The database name will be "SS_" followed by the name of the folder into which you have installed SilverStripe.  If this is enabled, it means that the phpinstaller will work out of the box without the installer needing to alter any files.  This helps prevent accidental changes to the environment. If `SS_DATABASE_CHOOSE_NAME` is an integer greater than one, then an ancestor folder will be used for the  database name.  This is handy for a site that's hosted from /sites/examplesite/www or /buildbot/allmodules-2.3/build. If it's 2, the parent folder will be chosen; if it's 3 the grandparent, and so on.|
| `SS_DEPRECATION_ENABLED` | Enable deprecation notices for this environment.|
| `SS_ENVIRONMENT_TYPE`| The environment type: dev, test or live.|
| `SS_DEFAULT_ADMIN_USERNAME`| The username of the default admin. This is a user with administrative privileges.|
| `SS_DEFAULT_ADMIN_PASSWORD`| The password of the default admin. This will not be stored in the database.|
| `SS_USE_BASIC_AUTH`| Protect the site with basic auth (good for test sites).<br/>When using CGI/FastCGI with Apache, you will have to add the `RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]` rewrite rule to your `.htaccess` file|
| `SS_SEND_ALL_EMAILS_TO`| If you define this constant, all emails will be redirected to this address.|
| `SS_SEND_ALL_EMAILS_FROM`| If you define this constant, all emails will be sent from this address.|
| `SS_ERROR_LOG` | Relative path to the log file. |
| `SS_PROTECTED_ASSETS_PATH` | Path to secured assets - defaults to ASSET_PATH/.protected |
| `SS_DATABASE_MEMORY` | Used for SQLite3 DBs |
| `SS_TRUSTED_PROXY_IPS` | IP address or CIDR range to trust proxy headers from. If left blank no proxy headers are trusted. Can be set to 'none' (trust none) or '*' (trust all) |
| `SS_ALLOWED_HOSTS` | A comma deliminated list of hostnames the site is allowed to respond to |
| `SS_MANIFESTCACHE` | The manifest cache to use (defaults to file based caching). Must be a CacheInterface or CacheFactory class name |
| `SS_IGNORE_DOT_ENV` | If set the .env file will be ignored. This is good for live to mitigate any performance implications of loading the .env file |
| `SS_BASE_URL` | The url to use when it isn't determinable by other means (eg: for CLI commands) |
| `SS_DATABASE_SSL_KEY` | Absolute path to SSL key file |
| `SS_DATABASE_SSL_CERT` | Absolute path to SSL certificate file |
| `SS_DATABASE_SSL_CA` | Absolute path to SSL Certificate Authority bundle file |
| `SS_DATABASE_SSL_CIPHER` | Optional setting for custom SSL cipher |
