# Installation

These instructions show you how to install SilverStripe on any web server.
Check out our operating system specific guides for [Linux](linux_unix),
[Windows Server](windows) and [Mac OSX](mac_osx).

## Installation Steps

*  Make sure the webserver has MySQL and PHP support (check our [server requirements](../server_requirements)).
*  Either [download the installer package](http://silverstripe.org/download), or [install through Composer](../composer).
*  If using with the installer download, extract it into your webroot.
*  Visit your domain or IP address in your web browser.
*  You will be presented with an installation wizard asking for database and login credentials.
*  After a couple of minutes, your site will be set up. Visit your site and enjoy!

## Issues?

If the above steps don't work for any reason have a read of the [Common Problems](common_problems) section.

<div class="notice" markdown="1">
SilverStripe ships with default rewriting rules specific to your web server. Apart from
routing requests to the framework, they also prevent access to sensitive files in the webroot,
for example YAML configuration files. Please refer to the [security](/topics/security) documentation for details.
</div>
