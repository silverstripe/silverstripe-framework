# Requirements

SilverStripe CMS needs to be installed on a web server. Content authors and website administrators use their web browser
to access a web-based GUI to do their day-to-day work. Website designers and developers require access to the files on
the server to update templates, website logic, and perform upgrades or maintenance.

Our web-based [PHP installer](installation/) can check if you meet the requirements listed below.

## Web server software requirements

SilverStripe 4 has the following server requirements:

 * PHP 5.6, 7.0, 7.1 or 7.2
   * Note: Although we do our best to support 5.6 and 7.0, they are deprecated and [unsupported by the PHP Project](http://php.net/supported-versions.php).
     If you are using these, we strongly recommend you to upgrade.
 * Allocate at least 48MB of memory to each PHP process. (SilverStripe can be resource hungry for some intensive operations.)
 * PHP requires a suitable CSPRNG (random number generator) source for generating random tokens, password salts etc. This can be any of the following, and most operating systems will have at least one source available:
   * PHP 7 `random_bytes()`:
     * `CryptGenRandom` (Windows only)
     * `arc4random_buf` (OpenBSD & NetBSD only)
     * `getrandom(2)` (Linux only)
     * `/dev/urandom`
   * PHP 5 [`random_compat`](https://github.com/paragonie/random_compat) polyfill:
     * libsodium
     * `/dev/urandom`
     * [`mcrypt_create_iv()`](http://php.net/manual/en/function.mcrypt-create-iv.php)
     * CAPICOM Utilities (`CAPICOM.Utilities.1`, Windows only)
 * Required modules: ctype, dom, fileinfo, hash, intl, mbstring, session, simplexml, tokenizer, xml.
 * At least one from each group of extensions:
     * Image library extension (gd2, imagick)
     * DB connector library (pdo, mysqli, pgsql)
 * Recommended configuration
     * Dev (local development for running test framework): memory_limit 512MB
     * Production: memory_limit = 64M

 * See [phpinfo()](http://php.net/manual/en/function.phpinfo.php) for more information about your environment
 * One of the following databases: 
   * MySQL 5.6+
   * PostgreSQL 9.4+ (requires ["silverstripe/postgresql" module](http://silverstripe.org/postgresql-module))
     * Warning: PostgreSQL has some known issues with collations when installed on Alpine, MacOS X and BSD derivatives
     (see [PostgreSQL FAQ](https://wiki.postgresql.org/wiki/FAQ#Why_do_my_strings_sort_incorrectly.3F)).  
     We do not support such installations, although they still may work correctly for you.  
     As a workaround for PostgreSQL 10+ you could manually switch to ICU collations (e.g. und-x-icu).
     There are no known workarounds for PostgreSQL <10.
   * [SQL Server](http://silverstripe.org/microsoft-sql-server-database/),
     [Oracle](https://github.com/smindel/silverstripe-oracle) and
     [SQLite](http://silverstripe.org/sqlite-database/) are not commercially supported, but are under development by our open source community.
 * One of the following web server products: 
   * Apache 2.0+ with mod_rewrite and "AllowOverride All" set
   * IIS 7+
   * Support for Lighttpd, IIS 6, and other web servers may work if you are familiar with configuring those products.
 * We recommend enabling content compression (for example with mod_deflate) to speed up the delivery of HTML, CSS, and JavaScript.
 * One of the following operating systems:
   * Linux/Unix/BSD
   * Windows
   * Mac OS X

### PHP Requirements for older SilverStripe releases

SilverStripe's PHP support has changed over time and if you are looking to upgrade PHP on your SilverStripe site, this table may be of use:

| SilverStripe Version | PHP Version | More information |
| -------------------- | ----------- | ---------------- |
| 3.0 - 3.5            | 5.3 - 5.6   | [requirements docs](https://docs.silverstripe.org/en/3.4/getting_started/server_requirements/)
| 3.6                  | 5.3 - 7.1   | |
| 3.7                  | 5.3 - 7.3   | [changelog](https://docs.silverstripe.org/en/3/changelogs/3.7.0/) |
| 4.0 - 4.4            | 5.6+        | |
| 4.5+ (unreleased)    | 7.1+        | [blog post](https://www.silverstripe.org/blog/our-plan-for-ending-php-5-6-support-in-silverstripe-4/) |


## Web server hardware requirements

Hardware requirements vary widely depending on the traffic to your website, the complexity of its logic (i.e., PHP), and
its size (i.e., database.) By default, all pages are dynamic, and thus access both the database and execute PHP code to
generate. SilverStripe can cache full pages and segments of templates to dramatically increase performance.

A typical website page on a conservative single CPU machine (e.g., Intel 2Ghz) takes roughly 300ms to generate. This
comfortably allows over a million page views per month. Caching and other optimisations can improve this by a factor of
ten or even one hundred times. SilverStripe CMS can be used in multiple-server architectures to improve scalability and
redundancy.

For more information on how to scale SilverStripe see the [Performance](/developer_guides/performance/) Guide.

## Client side (CMS) browser requirements

SilverStripe CMS supports the following web browsers:
* Google Chrome
* Internet Explorer 11
* Microsoft Edge 
* Mozilla Firefox.
 
We aim to provide satisfactory experiences in Apple Safari. SilverStripe CMS works well across Windows, Linux, and Mac operating systems.

## End user requirements

SilverStripe CMS is designed to make excellent, standards-compliant websites that are compatible with a wide range of
industry standard browsers and operating systems. A competent developer is able to produce websites that meet W3C
guidelines for HTML, CSS, JavaScript, and accessibility, in addition to meeting specific guide lines, such as
e-government requirements.
