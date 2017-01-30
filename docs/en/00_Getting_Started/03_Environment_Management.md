# Environment management

As part of website development and hosting it is natural for our sites to be hosted on several different environments.
These can be our laptops for local development, a testing server for customers to test changes on, or a production 
server.

For each of these environments we may require slightly different configurations for our servers. This could be our debug
level, caching backends, or - of course - sensitive information such as database credentials.

To solve this problem of setting variables per environment we use environment variables with the help of the 
[PHPDotEnv](https://github.com/vlucas/phpdotenv) library by Vance Lucas.

## Security considerations

Sensitive credentials should not be stored in a VCS or project code and should only be stored on the environment in 
question. When using live environments the use of `.env` files is discouraged and instead one should use "first class"
environment variables.

If you do use a `.env` file on your servers, you must ensure that external access to `.env` files is blocked by the
webserver.

## Managing environment variables with `.env` files

By default the `.env` must be placed in your project root (ie: same folder as you `composer.json`) or the parent
directory. If this file exists, it will be automatically loaded by the framework and the environment variables will be
set. An example `.env` file is included in the default installer named`.env.example`.

## Managing environment variables with Apache

You can set "real" environment variables using Apache. Please 
[see the Apache docs for more information](https://httpd.apache.org/docs/current/env.html)

## How to access the environment variables

Accessing the environment varaibles is easy and can be done using the `getenv` method or in the `$_ENV` and `$_SERVER`
super-globals:

```php
getenv('SS_DATABASE_CLASS');
$_ENV['SS_DATABASE_CLASS'];
$_SERVER['SS_DATABASE_CLASS'];
```

## Including an extra `.env` file

Sometimes it may be useful to include an extra `.env` file - on a shared local development environment where all
database credentials could be the same. To do this, you can add this snippet to your `mysite/_config.php` file:

```php
try {
	(new \Dotenv\Dotenv('/path/to/env/'))->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
	// no file found
}
```

## Core environment variables

SilverStripe core environment variables are listed here, though you're free to define any you need for your application.

| Name  | Description |
| ----  | ----------- |
| `SS_DATABASE_CLASS` | The database class to use, MySQLPDODatabase, MySQLDatabase, MSSQLDatabase, etc. defaults to MySQLDatabase.|
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
| `SS_TRUSTED_PROXY_PROTOCOL_HEADER` | Used to define the proxy header to be used to determine HTTPS status |
| `SS_TRUSTED_PROXY_IP_HEADER` | Used to define the proxy header to be used to determine request IPs |
| `SS_TRUSTED_PROXY_HOST_HEADER` | Used to define the proxy header to be used to determine the requested host name |
| `SS_TRUSTED_PROXY_IPS` | IP address or CIDR range to trust proxy headers from |
| `SS_ALLOWED_HOSTS` | A comma deliminated list of hostnames the site is allowed to respond to |
| `SS_MANIFESTCACHE` | The manifest cache to use (defaults to file based caching) |
| `SS_IGNORE_DOT_ENV` | If set the .env file will be ignored. This is good for live to mitigate any performance implications of loading the .env file |
| `SS_HOST` | The hostname to use when it isn't determinable by other means (eg: for CLI commands) |
