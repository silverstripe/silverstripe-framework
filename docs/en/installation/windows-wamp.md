# Windows with WAMPServer 2.5+

An easy and reliable approach to getting SilverStripe running on Windows is to use Apache, which can be conveniently
done through [WampServer](http://www.wampserver.com/en/). This can be useful if you are deploying on Linux Apache and
want a Microsoft Windows machine with a very similar environment.

Note: Installing on Microsoft's IIS webserver through Microsoft WebPI is likely to be easier, see
[installation-on-windows-pi](windows-pi).

## Install WAMP

1. Go to the [WampServer download page](http://www.wampserver.com/en/#download-wrapper).
2. You will first need to download and install the suggested [Visual C++ Redistributable for Visual Studio 2012 Update 4](http://www.microsoft.com/en-us/download/details.aspx?id=30679#) BEFORE installing WampServer.
3. Next, download the WampServer installer and the run the installer.  By default, it will install to C:\wamp.  You can choose your own install path if you wish; however note we will refer to the c:/wamp in the test of this tutorial.
4. Once WampServer has been installed and launched, you will see a small "W" in the task bar, next to
the clock.  If everything is working, then it will be green.  If it's orange or red, then something is likely misconfigured. See the Troubleshooting section below. If you can't see the "W", then WampServer hasn't been started and you should start WampServer from the start menu.
5. Left-click the "W", then select Apache -> Apache Modules -> Rewrite Module.  The "W" will flick to orange, and
then return to green.
6. Left-click the "W", then select MySQL -> my.ini. At the very bottom of the file, and add the following to a new line  without the quotes): "lower_case_table_names = 2". Save the file, close Notepad and left-click the "W", and
select 'Restart all services'. This is used to ease the transition between a Windows-based install and a Linux-based
install where database case-sensitivity is important.

## Install SilverStripe
### Composer
Composer is becoming our preferred way to manager installation and future dependancy management of SilverStripe modules. Getting started with Composer requires:
1. PHP installed on your local environment (which in this context is part of WAMP).
2. The Composer application itself (there is a Windows installer which will ask you to point it to PHP, in this case it should be at C:/wamp/bin/php/phpX.X.X/php.exe). 
See the [Composer documentation](https://getcomposer.org/doc/00-intro.md#installation-windows) to get the installer.
3. A command line such as windows command prompt or [gitbash](http://git-scm.com/download/win) (recommended and comes as part of git for windows).

Once you have installed the above, open a command line and use the following command to get a fresh copy of SilverStripe stable code installed into a 'silverstripe' sub-folder (note here we are using gitbash paths).

```bash
$ cd /c/wamp/www
$ composer create-project silverstripe/installer ./silverstripe
```

### Zip download
* [Download](http://silverstripe.org/stable-download) the latest SilverStripe CMS and Framework package
* Unpack the archive into `C:\wamp\www`
* Rename the unpacked directory from `C:\wamp\www\silverstripe-vX.X.X` to `C:\wamp\www\silverstripe`
 
### Install and configure
*  Visit `http://localhost/silverstripe` - you will see SilverStripe's installation screen.
* You should be able to click "Install SilverStripe" and the installer will do its thing.  It takes a minute or two.
* Once the installer has finished, visit `http://localhost/silverstripe`. You should see your new SilverStripe site's
home page.

## Troubleshooting
1. If there is some misconfiguration, this often indicated you may have another service on port 80 or port 3306. Here are some common sources of problems to check.  

After correcting these issues, left-click the "W" and choose 'restart all services'. It might a short while to restart, but the "W" turn green.

* You might have IIS running.  Check Start -> Control Panel -> Administrative Tools -> Internet Information
Services. Ensure that any web site services are stopped.
* If you run Skype, visit Select "Tools" -> "Options" in Skype's menu. Find an option "Use port 80 and 443 as
alternatives for incoming connection".  Make sure that it is de-selected.

2. Vista's security controls can cause issues. If you have installed on Vista but you're getting errors, there is a chance that SilverStripe does not have sufficient permissions.

Right clicked on the installation folder and go to Permissions > Security > Users > Advanced and give the user full
control. 

3. If you find you are having issues with URL rewriting. Remove the index.php file that is bundled with SilverStripe. As we are using Apache web server's URL rewriting this file is not required (and in fact can result in problems when using apache 2.4+ as in the latest versions of WAMP). The other option is to enable the mod_access_compat module for apache which improves compatibility of newer versions of Apache with SilverStripe. 