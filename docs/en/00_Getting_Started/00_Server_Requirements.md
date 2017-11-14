# Requirements

SilverStripe CMS needs to be installed on a web server. Content authors and website administrators use their web browser
to access a web-based GUI to do their day-to-day work. Website designers and developers require access to the files on
the server to update templates, website logic, and perform upgrades or maintenance.

Our web-based [PHP installer](installation/) can check if you meet the requirements listed below.

## Web server software requirements

 * PHP 5.3.3+, <7.2
 * We recommend using a PHP accelerator or opcode cache, such as [xcache](http://xcache.lighttpd.net/) or [WinCache](http://www.iis.net/download/wincacheforphp).
     * Note: Some PHP 5.5+ packages already have [Zend OpCache](http://php.net/manual/en/book.opcache.php) installed by default. If this is the case on your system, do not try and run additional opcaches alongside Zend OpCache without first disabling it, as it will likely have unexpected consequences.
 * Allocate at least 48MB of memory to each PHP process. (SilverStripe can be resource hungry for some intensive operations.)
 * Required modules: dom, gd2, fileinfo, hash, iconv, mbstring, mysqli (or other database driver), session, simplexml, tokenizer, xml.
 * Recommended configuration

		safe_mode = Off
		magic_quotes_gpc = Off
		memory_limit = 48M

 * See [phpinfo()](http://php.net/manual/en/function.phpinfo.php) for more information about your environment
 * One of the following databases: 
  * MySQL 5.0+
  * PostgreSQL 8.3+ (requires ["postgresql" module](http://silverstripe.org/postgresql-module))
  * SQL Server 2008+ (requires ["mssql" module](http://silverstripe.org/microsoft-sql-server-database/))
  * Support for [Oracle](http://www.silverstripe.org/oracle-database-module/) and [SQLite](http://silverstripe.org/sqlite-database/) is not commercially supported, but is under development by our open source community.
 * One of the following web server products: 
  * Apache 2.0+ with mod_rewrite and "AllowOverride All" set
  * IIS 7+
  * Support for Lighttpd, IIS 6, and other web servers may work if you are familiar with configuring those products.
 * We recommend enabling content compression (for example with mod_deflate) to speed up the delivery of HTML, CSS, and JavaScript.
 * One of the following operating systems:
  * Linux/Unix/BSD
  * Microsoft Windows XP SP3, Vista, Windows 7, Server 2008, Server 2008 R2
  * Mac OS X 10.4+

### Does SilverStripe 3 work with PHP 7?
SilverStripe 3.6 or greater will work with PHP 7.0 and 7.1. SilverStripe 3.5 or lower will only work with PHP
5.3 - 5.6.

## Web server hardware requirements

Hardware requirements vary widely depending on the traffic to your website, the complexity of its logic (i.e., PHP), and
its size (i.e., database.) By default, all pages are dynamic, and thus access both the database and execute PHP code to
generate. SilverStripe can cache full pages and segments of templates to dramatically increase performance.

A typical website page on a conservative single CPU machine (e.g., Intel 2Ghz) takes roughly 300ms to generate. This
comfortably allows over a million page views per month. Caching and other optimisations can improve this by a factor of
ten or even one hundred times. SilverStripe CMS can be used in multiple-server architectures to improve scalability and
redundancy.

For more information on how to scale SilverStripe see the [Performance](/developer_guides/performance/) Guide.

## Client side (CMS) requirements

SilverStripe CMS is designed to work well with Google Chrome, Mozilla Firefox and Internet Explorer 8+. We aim to
provide satisfactory experiences in Apple Safari. SilverStripe CMS works well across Windows, Linux, and Mac operating
systems.

## End user requirements

SilverStripe CMS is designed to make excellent, standards-compliant websites that are compatible with a wide range of
industry standard browsers and operating systems. A competent developer is able to produce websites that meet W3C
guidelines for HTML, CSS, JavaScript, and accessibility, in addition to meeting specific guide lines, such as
e-government requirements.
