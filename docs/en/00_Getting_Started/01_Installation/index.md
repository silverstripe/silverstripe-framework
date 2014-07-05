# Installation

These instructions show you how to install SilverStripe on any web server. 

The best way to install from the source code is to use [Composer](composer).

For additional information about installing SilverStripe on specific operation systems, refer to:

*  [Installation on a Windows Server](windows-pi)
*  [Installation on OSX](mac-osx)

## Installation Steps

*  [Download](http://silverstripe.org/download) the installer package

*  Make sure the webserver has MySQL and PHP support.  See [Server Requirements](server-requirements) for more
information. 

*  Unpack the installer somewhere into your web-root. Usually the www folder or similar. Most downloads from SilverStripe
are compressed tarballs. To extract these files you can either do them natively (Unix) or with 7-Zip (Windows)

*  Visit your sites Domain or IP Address in your web browser.

*  You will be presented with a form where you enter your MySQL login details and are asked to give your site a 'project
name' and the default login details. Follow the questions and select the *install* button at the bottom of the page.

*  After a couple of minutes, your site will be set up. Visit your site and enjoy!

## Issues?

If the above steps don't work for any reason have a read of the [Common Problems](common-problems) section.

## Security notes

### Yaml

For the reasons explained in [security](/topics/security), Yaml files are blocked by default by the .htaccess file
provided by the SilverStripe installer module.

To allow serving yaml files from a specific directory, add code like this to an .htaccess file in that directory

	<Files *.yml>
		Order allow,deny
		Allow from all
	</Files>
