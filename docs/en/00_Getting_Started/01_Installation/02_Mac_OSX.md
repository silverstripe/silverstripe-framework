# Mac OSX with MAMP

This topic covers setting up your Mac as a web server and installing SilverStripe.

OSX comes bundled with PHP and Apache, but you're stuck with the versions it ships with.
It is also a bit harder to install additional PHP modules required by SilverStripe.
[MAMP](http://www.mamp.info/en/) is a simple way to get a complete webserver
environment going on your OSX machine, without removing or altering any system-level configuration.

Check out the [MAC OSX with Homebrew](other_installation_options/Mac_OSX_Homebrew)
for an alternative, more configurable installation process.

## Requirements

Please check the [system requirements](http://www.mamp.info/en/documentation/) for MAMP,
you'll need a fairly new version of OSX to run it.

## MAMP Installation

 * [Download MAMP](http://www.mamp.info/en/)
 * Install and start MAMP
 * Check out your new web server environment on `http://localhost:8888`

## SilverStripe Installation

[Composer](http://getcomposer.org) is a dependancy manager for PHP, and the preferred way to
install SilverStripe. It ensures that you get the correct set of files for your project.
Composer uses your MAMP PHP executable to run and also requires [git](http://git-scm.com)
to automatically download the required files from GitHub and other repositories.

In order to install Composer, we need to let the system know where to find the PHP executable.
Open or create the `~/.bash_profile` file in your home folder, then add the following line:
`export PATH=/Applications/MAMP/bin/php/php5.5.22/bin:$PATH`
You'll need to adjust the PHP version number (`php5.5.22`). The currently running PHP version is shown on `http://localhost:8888/MAMP/index.php?page=phpinfo`.
Run `source ~/.bash_profile` for the changes to take effect. You can verify that the correct executable
is used by running `which php`. It should show the path to MAMP from above.

Now you're ready to install Composer: Run `curl -sS https://getcomposer.org/installer | php`.
We recommend that you make the `composer` executable available globally,
which requires moving the file to a different folder. Run `mv composer.phar /usr/local/bin/composer`.
More detailed installation instructions are available on [getcomposer.org](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).
You can verify the installation by typing the `composer` command, which should show you a command overview.

Finally, we're ready to install SilverStripe through composer:
`composer create-project silverstripe/installer /Applications/MAMP/htdocs/silverstripe/`.
After finishing, the installation wizard should be available at `http://localhost:8888/silverstripe`.
The MAMP default database credentials are user `root` and password `root`.

We have a separate in-depth tutorial for [Composer Installation and Usage](composer).