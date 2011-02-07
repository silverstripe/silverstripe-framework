# Mac OSX

This topic covers setting up your Mac as a Web Server and installing SilverStripe. 

While OSX Comes bundled with PHP and Apache (Thanks Apple!) Its not quite ideal for SilverStripe so for setting up a
webserver on OSX we suggest using [MAMP](http://www.mamp.info/en/index.php) or using [MacPorts](http://www.macports.org/) 
to manage your packages.

If you want to use the default OSX PHP version then you will need to recompile your own versions of PHP with GD. Providing instructions
for how to recompile PHP is beyond the scope of our documentation but try a online search.

## Installing MAMP

If you have decided to install using MacPorts you can skip this section.

Once you have downloaded and Installed MAMP start the Application and Make sure everything is running by clicking the
MAMP icon. Under `Preferences -> PHP` make sure Version 5 is Selected.

Open up `/Applications/MAMP/conf/PHP5/php.ini` and make the following configuration changes:

	memory_limit = 64M

Once you make that change open the MAMP App Again by clicking on the MAMP Icon and click Stop Servers then Start
Servers - this is so our changes to the php.ini take effect.

## Installing SilverStripe

[Download](http://silverstripe.org/download) the latest SilverStripe installer package. Copy the tar.gz file to the 'Document Root' for MAMP - By Default its `/Applications/MAMP/htdocs`.
Don't know what your Document Root is? Open MAMP Click `Preferences -> Apache`. 

Extract the tar.gz file to a folder, e.g. `silverstripe/` (you always move the tar.gz file first and not the other way
around as SilverStripe uses a '.htaccess' file which is hidden from OSX so if you move SilverStripe the .htaccess file
won't come along. 

Open your web browser and go to `http://localhost:8888/silverstripe/`. Enter your database details - by default with MAMP its user `root` and password  `root` and select your account details. Click "Check Details".

Once everything is sorted hit Install! and Voila you have SilverStripe installed 