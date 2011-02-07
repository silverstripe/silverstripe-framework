# Windows with WAMPServer

An easy and reliable approach to getting SilverStripe running on Windows is to use Apache, which can be convieniently
done through [WampServer](http://www.wampserver.com/en/). This can be useful if you are deploying on Linux Apache and
want a Microsoft Windows machine with a very similar environment.

Note: Installing on Microsoft's IIS webserver through Microsoft WebPI is likely to be easier, see
[installation-on-windows-pi](windows-pi).

## Install WAMP

1.  Download WampServer from http://www.wampserver.com/en/download.php
2.  Run the installer.  By default, it will install to C:\wamp.  You can choose your own install path if you wish; the
directories mentioned below will also be different.
3.  Once WampServer has been installed and launched, you will see a little half circle gauge in the task bar, next to
the clock.  If everything is working, then it will be white.  If it's yellow or red, then something is wrong.  If you
can't see the gauge, then WampServer hasn't been started and you should start WampServer from the start menu.
4.  If something's wrong, this usually means that you have another service on port 80 or port 3306.   Here are some
common sources of problems to check.  After correcting these issues, left-click the gauge and choose 'restart all
services'.  It might a short while to restart, but the gauge should go to white.

    * You might have IIS running.  Check Start -> Control Panel -> Administrative Tools -> Internet Information
Services.   Ensure that any web site services are stopped.
    * If you run Skype, visit Select "Tools" -> "Options" in Skype's menu.  Find an option "Use port 80 and 443 as
alternatives for incoming connection".  Make sure that it is de-selected.
5.  Left-click the gauge, then select Apache -> Apache Modules -> Rewrite Module.  The gauge will flick to yellow, and
then return to white.
6.  Left-click the gauge, then select MySQL -> my.ini. At the very bottom of the file, and add the following to a new
line (without the quotes): "lower_case_table_names = 2". Save the file, close Notepad and left-click the gauge, and
selected 'Restart all services'. This is used to ease the transition between a Windows-based install and a Linux-based
install where case-sensitivity is important.

## Install SilverStripe

 * [Download](http://silverstripe.org/download) the latest SilverStripe installer package
 *  Unpack the archive into `C:\wamp\www`
 *  Rename the unpacked directory from `C:\wamp\www\silverstripe-vX.X.X` to `C:\wamp\www\silverstripe`
 *  Visit `http://localhost/silverstripe` - you will see SilverStripe's installation screen.
 * You should be able to click "Install SilverStripe" and the installer will do its thing.  It takes a minute or two.
 * Once the installer has finished, visit `http://localhost/silverstripe`.  You should see your new SilverStripe site's
home page.

## Troubleshooting

Vista's security controls can cause issues. If you have installed on Vista but you're getting errors, there is a chance
that SilverStripe does not have sufficient permissions.

Right clicked on the installation folder and go to Permissions > Security > Users > Advanced and give the user full
control. 