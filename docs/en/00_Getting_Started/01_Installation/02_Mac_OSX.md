# Mac OSX

This topic covers setting up your Mac as a Web Server and installing SilverStripe.

While OSX Comes bundled with PHP and Apache (Thanks Apple!) Its not quite ideal for SilverStripe so for setting up a
webserver on OSX we suggest using [MAMP](http://www.mamp.info/en/index.php) or using [MacPorts](http://www.macports.org/)
to manage your packages.

If you want to use the default OSX PHP version then you will need to recompile your own versions of PHP with GD. Providing instructions
for how to recompile PHP is beyond the scope of our documentation but try an online search.

## Installing MAMP

If you have decided to install using MacPorts you can skip this section.

Once you have downloaded and Installed MAMP start the Application and Make sure everything is running by clicking the
MAMP icon. Under `Preferences -> PHP` make sure Version 5 is Selected.

Open up `/Applications/MAMP/conf/PHP5/php.ini` and make the following configuration changes:

	memory_limit = 64M

Once you make that change open the MAMP App Again by clicking on the MAMP Icon and click Stop Servers then Start
Servers - this is so our changes to the php.ini take effect.

## Installing SilverStripe

### Composer
[Composer (a dependancy manager for PHP)](http://getcomposer.org) is the preferred way to install SilverStripe and ensure you get the correct set of files for your project.

Composer uses your MAMP PHP executable to run and also requires [git](http://git-scm.com) (so it can automatically download the required files from GitHub).

#### Install composer using MAMP
 1. First create an alias for our bash profile, using your preferred terminal text editor (nano, vim, etc) open `~/.bash_profile`.

 2. Add the following line (adjusting the version number of PHP to your installation of MAMP): `alias phpmamp='/Applications/MAMP/bin/php/php5.4.10/bin/php'`.

 3. The run `. ~/.bash_profile` to reload the bash profile and make it accessible.

 4. This will create an alias, `phpmamp`, allowing you to use the MAMP installation of PHP. Please take note of the PHP version, in this case 5.4.10, as with different versions of MAMP this may be different. Check your installation and see what version you have, and replace the number accordingly (this was written with MAMP version 2.1.2).

 5. With that setup, we are ready to install `composer`. This is a two step process if we would like this to be installed globally (only do the first step if you would like `composer` installed to the local working directory only).
    - First, run the following command in the terminal: `curl -sS https://getcomposer.org/installer | phpmamp`

	We are using `phpmamp` so that we correctly use the MAMP installation of PHP from above.

	- Second, if you want to make composer available globally, we need to move the file to '/usr/local/bin/composer'. To do this, run the following command:
	`sudo mv composer.phar /usr/local/bin/composer`

	Terminal will ask you for your root password, after entering it and pressing the 'return' (or enter) key, you'll have a working global installation of composer on your mac that uses MAMP.

 6. You can verify your installation worked by typing the following command:
	`composer`
	It'll show you the current version and a list of commands you can use.

 7. Run the following command to get a fresh copy of SilverStripe via composer:

        `composer create-project silverstripe/installer /Applications/MAMP/htdocs/silverstripe/`

 8. You can now [use composer](http://doc.silverstripe.org/framework/en/getting_started/composer/) to manage future SilverStripe updates and adding modules with a few easy commands.


### Package Download

[Download](http://silverstripe.org/software/download/) the latest SilverStripe installer package. Copy the tar.gz or zip file to the 'Document Root' for MAMP - By Default its `/Applications/MAMP/htdocs`.
Don't know what your Document Root is? Open MAMP Click `Preferences -> Apache`.

Extract the tar.gz file to a folder, e.g. `silverstripe/` (you always move the tar.gz file first and not the other way
around as SilverStripe uses a '.htaccess' file which is hidden from OSX so if you move SilverStripe the .htaccess file
won't come along.

### Run the installation wizard
Once you have a copy of the required code (by either of the above methods), open your web browser and go to `http://localhost:8888/silverstripe/`. Enter your database details - by default with MAMP its user `root` and password  `root` and select your account details. Click "Check Details".

Once everything is sorted hit "Install!" and Voila,  you have SilverStripe installed
