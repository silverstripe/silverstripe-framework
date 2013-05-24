# Installing SilverStripe

## Download

SilverStripe is a web application. This means that you will need to have a webserver and database meeting its 
[requirements](server-requirements). We will take you through the setup of the server environment as well the application itself.

<div markdown='1' style="float: right; margin-left: 20px">
![](../_images/composer.png)
</div>

## Getting the code

The best way to get SilverStripe is to [install with Composer](composer). Composer is a package management tool for PHP that
lets you install and upgrade SilverStripe and its modules.  Although installing Composer is one extra step, it will give you much more flexibility than just downloading the file from silverstripe.org.

Other ways to get SilverStripe:

 * If you just want to get the code as quickly as possible, you can [download SilverStripe from our website](http://silverstripe.org/download).
 * If you already have an installed version of SilverStripe, and you haven't used Composer to get it, please see our [upgrading](upgrading) guide.  Note that [Composer](composer) provides its own tools for upgrading.

## Setting up a server

### Linux/Unix

To run SilverStripe on Linux/Unix, set up one of the following web servers: 

*  [Install using Apache](webserver) - our preferred platform
*  [Install using Lighttpd](lighttpd) - fast, but a bit tricker to get going
*  [Install using Nginx](nginx) - Super fast at serving static files. Great for large traffic sites.

### Windows

The most straightforward way to get SilverStripe running on Windows is with the [Microsoft Web Platform installer](windows-pi).  You can skip the "getting the code" step.

For more flexibility, you can set up either of the following web servers, and use Composer to get the code:

 * [Install using IIS](windows-manual-iis)
 * [Install using Apache/WAMP](windows-wamp)

### Mac OS X

Mac OS X comes with a built-in webserver, but there are a number of other options:

 * [Install using MAMP](mac-osx)
 * Install using the built-in webserver (no docs yet)
 * Install using MacPorts (no docs yet)

## Troubleshooting

If you run into trouble, see [common-problems](common-problems) or post to the 
[SilverStripe forums](http://silverstripe.com/silverstripe-forum/).

## Related

 * [Module installation](../topics/modules)
 * [Suggested web hosts](http://doc.silverstripe.org/old/suggested-web-hosts)
