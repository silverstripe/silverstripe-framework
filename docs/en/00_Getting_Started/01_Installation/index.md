# Installation

These instructions show you how to install SilverStripe on any web server. 
The best way to install from the source code is to use [Composer](../composer).
Check out our operating system specific guides for [Linux](linux_unix),
[Windows Server](windows-pi) and [Mac OSX](mac-osx).

## Installation Steps

*  [Download](http://silverstripe.org/download) the installer package
*  Make sure the webserver has MySQL and PHP support.  See [Server Requirements](server-requirements) for more information. 
*  Unpack the installer somewhere into your web-root. Usually the www folder or similar. Most downloads from SilverStripe
are compressed tarballs. To extract these files you can either do them natively (Unix) or with 7-Zip (Windows)
*  Visit your sites domain or IP address in your web browser.
*  You will be presented with a form where you enter your MySQL login details and are asked to give your site a 'project
name' and the default login details. Follow the questions and select the *install* button at the bottom of the page.
*  After a couple of minutes, your site will be set up. Visit your site and enjoy!

## Issues?

If the above steps don't work for any reason have a read of the [Common Problems](common-problems) section.

<div class="notice" markdown="1">
SilverStripe ships with default rewriting rules specific to your web server. Apart from
routing requests to the framework, they also prevent access to sensitive files in the webroot,
for example YAML configuration files. Please refer to the [security](/topics/security) documentation for details.
</div>
