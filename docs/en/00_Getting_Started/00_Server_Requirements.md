---
title: Server Requirements
icon: server
summary: What you will need to run Silverstripe CMS on a web server 
---


# Requirements

Silverstripe CMS needs to be installed on a web server. Content authors and website administrators use their web browser
to access a web-based GUI to do their day-to-day work. Website designers and developers require access to the files on
the server to update templates, website logic, and perform upgrades or maintenance.

## PHP

 * PHP >=7.1
 * PHP extensions: `ctype`, `dom`, `fileinfo`, `hash`, `intl`, `mbstring`, `session`, `simplexml`, `tokenizer`, `xml`
 * PHP configuration: `memory_limit` with at least `48M`
 * PHP extension for image manipulation: Either `gd` or `imagick`
 * PHP extension for a database connector (e.g. `pdo` or `mysqli`)

Use [phpinfo()](http://php.net/manual/en/function.phpinfo.php) to inspect your configuration.

## Database

 * MySQL >=5.6 (built-in, [commercially supported](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/))
 * PostgreSQL ([third party module](https://addons.silverstripe.org/add-ons/silverstripe/postgresql), community supported)
 * SQL Server ([third party module](https://addons.silverstripe.org/add-ons/silverstripe/mssql), community supported)
 * SQLite ([third party module](https://addons.silverstripe.org/add-ons/silverstripe/sqlite3), community supported)
 
## Webserver Configuration

### Overview

SilverStripe needs to handle a variety of HTTP requests,
and relies on the hosting environment to be configured securely to
enforce restrictions. There are secure defaults in place for Apache,
but you should be aware of the configuration regardless of your webserver setup.

### Public webroot

The webroot of your webserver should be configured to the `public/` subfolder.
Projects created prior to SilverStripe 4.1 might be using the main project
folder as the webroot. In this case, you are responsible for ensuring
access to system files such as configuration in `*.yml` is protected
from public access. We strongly recommend switching to more secure
hosting via the `public/`. See [4.1.0 upgrading guide](/changelogs/4.1.0).

### Filesystem permissions

SilverStripe needs write access for the webserver user to `public/assets`,
and read access for that user on everything else in your webroot.

### Assets

SilverStripe allows CMS authors to upload files into the `public/assets/` folder,
which should be served by your webserver. **No PHP execution should be allowed in this folder**.
This is configured for Apache by default via `public/assets/.htaccess`.
The file is generated dynamically during the `dev/build` stage.

Additionally, access is whitelisted by file extension through a
dynamically generated whitelist based on the `File.allowed_extensions` setting
(see [File Security](/developer_guides/files/file_security#file-types)).
This whitelist uses the same defaults configured through file upload
through SilverStripe, so is considered a second line of defence.

### Secure Assets

Files can be kept in draft stage,
and access restricted to certain user groups.
These files are stored in a special `.protected` folder (defaulting to `public/assets/.protected`).
**Requests to files in this folder should be denied by your webserver**.

Requests to files in the `.protected` folder
are routed to PHP by default when using Apache, through `public/assets/.htaccess`.
If you are using another webserver, please follow our guides to ensure a secure setup.
See [Developer Guides: File Security](/developer_guides/files/file_security) for details.


### Web Worker Concurrency

It's generally a good idea to run multiple workers to serve multiple HTTP requests
to SilverStripe concurrently. The exact number depends on your website needs.
The CMS attempts to request multiple views concurrently.
It also routes [protected and draft files](/developer_guides/files/file_security)
through SilverStripe. This can increase your concurrency requirements,
e.g. when authors batch upload and view dozens of draft files in the CMS.

When allowing upload of large files through the CMS (through PHP settings),
these files might be used as [protected and draft files](/developer_guides/files/file_security).
Files in this state get served by SilverStripe rather than your webserver.
Since the framework uses [PHP streams](https://www.php.net/manual/en/ref.stream.php),
this allows serving of files larger than your PHP memory limit.
Please be aware that streaming operations don't count towards
PHP's [max_execution_time](https://www.php.net/manual/en/function.set-time-limit.php),
which can risk exhaustion of web worker pools for long-running downloads.

### URL Rewriting

SilverStripe expects URL paths to be rewritten to `public/index.php`.
For Apache, this is preconfigured through `.htaccess` files,
and expects using the `mod_rewrite` module.
By default, these files are located in `public/.htaccess` and `public/assets/.htaccess`.

### HTTP Headers

SilverStripe can add HTTP headers to reponses it handles directly.
These headers are often sensitive, for example preventing HTTP caching for responses
displaying data based on user sessions, or when serving protected assets.
You need to ensure those headers are kept in place in your webserver.
For example, Apache allows this through `Header setifempty` (see [docs](https://httpd.apache.org/docs/current/mod/mod_headers.html#header)).
See [Developer Guide: Performance](/developer_guides/performance/)
and [Developer Guides: File Security](/developer_guides/files/file_security) for more details.

### Symlinks

SilverStripe is a modular system, with modules installed and updated
via the `composer` PHP dependency manager. These are usually stored in `vendor/`,
outside of the `public/` webroot. Since many modules rely on serving frontend assets
such as CSS files or images, these are mapped over to the `public/_resources/` folder automatically.
If the filesystem supports it, this is achieved through symlinks.
Depending on your hosting and deployment mechanisms,
you may need to configure the plugin to copy files instead.
See [silverstripe/vendor-plugin](https://github.com/silverstripe/vendor-plugin) for details.

### Error pages

The default installation includes [silverstripe/errorpage](https://addons.silverstripe.org/add-ons/silverstripe/errorpage),
which generates static error pages that bypass PHP execution when those pages are published in the CMS.
Once published, the static files are located in `public/assets/error-404.html` and `public/assets/error-500.html`.
The default `public/.htaccess` file is configured to have Apache serve those pages based on their HTTP status code. 

### Other webservers (Nginx, IIS, Lighttpd)

Serving through webservers other than Apache requires more manual configuration,
since the defaults configured through `.htaccess` don't apply.
Please apply the considerations above to your webserver to ensure a secure hosting environment.
In particular, configure protected assets correctly to avoid exposing draft or protected files uploaded through the CMS. 

There are various community supported installation instructions for different environments.
Nginx is a popular choice, see [Nginx webserver configuration](https://forum.silverstripe.org/t/nginx-webserver-configuration/2246).

SilverStripe is known to work with Microsoft IIS, and generates `web.config` files by default
(see [Microsoft IIS and SQL Server configuration](https://forum.silverstripe.org/t/microsoft-iis-webserver-and-sql-server-support/2245)).

Additionally, there are community supported guides for installing SilverStripe
on various environments:

 * [Hosting via Bitnami](https://bitnami.com/stack/silverstripe/virtual-machine): In the cloud or as a locally hosted virtual machine
 * [Vagrant/Virtualbox with CentOS](https://forum.silverstripe.org/t/installing-via-vagrant-virtualbox-with-centos/2248)
 * [macOS with Homebrew](https://forum.silverstripe.org/t/installing-on-osx-with-homebrew/2247)
 * [macOS with MAMP](https://forum.silverstripe.org/t/installing-on-osx-with-mamp/2249)
 * [Windows with WAMP](https://forum.silverstripe.org/t/installing-on-windows-via-wamp/2250)
 * [Vagrant with silverstripe-australia/vagrant-environment](https://github.com/silverstripe-australia/vagrant-environment)
 * [Vagrant with BetterBrief/vagrant-skeleton](https://github.com/BetterBrief/vagrant-skeleton)

## PHP Requirements for older SilverStripe releases {#php-support}

SilverStripe's PHP support has changed over time and if you are looking to upgrade PHP on your SilverStripe site, this table may be of use:

| SilverStripe Version | PHP Version | More information |
| -------------------- | ----------- | ---------------- |
| 3.0 - 3.5            | 5.3 - 5.6   | [requirements docs](https://docs.silverstripe.org/en/3.4/getting_started/server_requirements/)
| 3.6                  | 5.3 - 7.1   | |
| 3.7                  | 5.3 - 7.3   | [changelog](https://docs.silverstripe.org/en/3/changelogs/3.7.0/) |
| 4.0 - 4.4            | 5.6+        | |
| 4.5+ (unreleased)    | 7.1+        | [blog post](https://www.silverstripe.org/blog/our-plan-for-ending-php-5-6-support-in-silverstripe-4/) |
| 5.0+ (unreleased)    | 7.2+        | |


## CMS browser requirements

SilverStripe CMS supports the following web browsers:
* Google Chrome
* Internet Explorer 11
* Microsoft Edge 
* Mozilla Firefox
 
We aim to provide satisfactory experiences in Apple Safari. SilverStripe CMS works well across Windows, Linux, and Mac operating systems.

## End user requirements

SilverStripe CMS is designed to make excellent, standards-compliant websites that are compatible with a wide range of
industry standard browsers and operating systems. A competent developer is able to produce websites that meet W3C
guidelines for HTML, CSS, JavaScript, and accessibility, in addition to meeting specific guide lines, such as
e-government requirements.
