# Requirements

SilverStripe CMS needs to be installed on a web server. Content authors and website administrators use their web browser
to access a web-based GUI to do their day-to-day work. Website designers and developers require access to the files on
the server to update templates, website logic, and perform upgrades or maintenance.

Our web-based [PHP installer](installation/) can check if you meet the requirements listed below.

## Web server software requirements

 * PHP 5.6 and PHP 7.x
 * Once PHP versions become [unsupported by the PHP Project](http://php.net/supported-versions.php),
   we drop support for those versions in the [next minor release](/contributing/release-process). This means that PHP 5.6 support may be dropped in a 4.x minor release after December 2018.
 * We recommend using a PHP accelerator or opcode cache, such as [xcache](http://xcache.lighttpd.net/) or [WinCache](http://www.iis.net/download/wincacheforphp).
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
   * MySQL 5.0+
   * PostgreSQL 8.3+ (requires ["postgresql" module](http://silverstripe.org/postgresql-module))
   * [SQL Server 2008+](http://silverstripe.org/microsoft-sql-server-database/), [Oracle](https://github.com/smindel/silverstripe-oracle) and [SQLite](http://silverstripe.org/sqlite-database/) are not commercially supported, but are under development by our open source community.
 * One of the following web server products: 
   * Apache 2.0+ with mod_rewrite and "AllowOverride All" set
   * IIS 7+
   * Support for Lighttpd, IIS 6, and other web servers may work if you are familiar with configuring those products.
 * We recommend enabling content compression (for example with mod_deflate) to speed up the delivery of HTML, CSS, and JavaScript.
 * One of the following operating systems:
   * Linux/Unix/BSD
   * Microsoft Windows XP SP3, Vista, Windows 7, Server 2008, Server 2008 R2
   * Mac OS X 10.4+

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

SilverStripe CMS is designed to work well with Google Chrome, Mozilla Firefox and Internet Explorer 11+. We aim to
provide satisfactory experiences in Apple Safari. SilverStripe CMS works well across Windows, Linux, and Mac operating
systems.

## End user requirements

SilverStripe CMS is designed to make excellent, standards-compliant websites that are compatible with a wide range of
industry standard browsers and operating systems. A competent developer is able to produce websites that meet W3C
guidelines for HTML, CSS, JavaScript, and accessibility, in addition to meeting specific guide lines, such as
e-government requirements.
