# Install SilverStripe manually on Windows using IIS 6

How to prepare Windows Server 2003 for SilverStripe using IIS 6 and FastCGI.

This guide will work for the following operating systems:

  * Windows Server 2003
  * Windows Server 2003 R2

Database install and configuration is not covered here, it is assumed you will do this yourself.

PHP comes with MySQL support out of the box, but you will need to install the [SQL Server Driver for PHP](http://www.microsoft.com/downloads/en/details.aspx?displaylang=en&FamilyID=80e44913-24b4-4113-8807-caae6cf2ca05)
from Microsoft if you want to use SQL Server.

## Preparation

Open **Windows Update** and make sure everything is updated, including optional updates. It is important that all .NET Framework updates including service packs are installed.

## Install IIS

  - Open **Control Panel** > **Add/Remove Programs** 
  - Click **Add/Remove Windows Components** on the left hand bar
  - Check **Application Server** and then click **Next** to install it

## Install FastCGI for IIS

  - Download and install this package: http://www.iis.net/download/fastcgi
  - Open **inetmgr.exe**
  - Right click **Web Sites** and go to **Properties**
  - Click the **Home Directory** tab
  - Click **Configuration...** then **Add**
  - In the **Add/Edit Extension Mapping** dialog, click **Browse...** and navigate to fcgiext.dll which is located in %windir%\system32\inetsrv
  - In the **Extension** text box, enter **.php**
  - Under **Verbs** in the **Limit to** text box, enter **GET,HEAD,POST**
  - Ensure that the **Script engine** and **Verify that file exists** boxes are checked then click **OK**
  - Open fcgiext.ini located in %windir%\system32\inetsrv. In the [Types] section of the file, add **php=PHP**
  - Create a new section called **[PHP]** at the bottom of the file, like this:

	[PHP]
	ExePath=c:\php5\php-cgi.exe

Finally, run these commands in **Command Prompt**

	cd %windir%\system32\inetsrv
	cscript fcgiconfig.js -set -section:"PHP" -InstanceMaxRequests:10000
	cscript fcgiconfig.js -set -section:"PHP" -EnvironmentVars:PHP_FCGI_MAX_REQUESTS:10000
	cscript fcgiconfig.js -set -section:"PHP" -ActivityTimeout:300

## Install PHP

  - [Download PHP](http://windows.php.net/download) (**Zip** link underneath the **VC9 x86 Non Thread Safe** section)
  - [Download WinCache](http://www.iis.net/download/WinCacheForPHP) (**WinCache 1.1 for PHP 5.3**)
  - Extract the PHP zip contents to **c:\php5**
  - Run the WinCache self-extractor and extract to **c:\php5\ext**. A file called **php_wincache.dll** should now reside in **c:\php5\ext**
  - Rename **php.ini-development** to **php.ini** in **c:\php5**
  - Open **php.ini**, located in **c:\php5** with **Notepad** or another editor like **Notepad++**
  - Search for **date.timezone**, uncomment it by removing the semicolon and set a timezone from here: http://php.net/manual/en/timezones.php
  - Search for **fastcgi.impersonate**, uncomment it by removing the semicolon and set it like this: **fastcgi.impersonate = 1**
  - Search for **cgi.fix_pathinfo**, uncomment it by removing the semicolon and set it like this: **cgi.fix_pathinfo = 1**
  - Search for **cgi.force_redirect**, uncomment it by removing the semicolon and set it like this: **cgi.force_redirect = 0**
  - Search for **fastcgi.logging**, uncomment it by removing the semicolon and set it like this: **fastcgi.logging = 0**
  - Search for **extension_dir** and make sure it looks like this: **extension_dir = "ext"** (use proper double quotation characters here)
  - Find the "Dynamic Extensions" part of the file, and replace all extension entries with the following:

	;extension=php_bz2.dll
	extension=php_curl.dll
	;extension=php_enchant.dll
	;extension=php_exif.dll
	;extension=php_fileinfo.dll
	extension=php_gd2.dll
	;extension=php_gettext.dll
	;extension=php_gmp.dll
	;extension=php_imap.dll
	;extension=php_intl.dll
	;extension=php_ldap.dll
	extension=php_mbstring.dll
	extension=php_mysql.dll
	extension=php_mysqli.dll
	;extension=php_oci8.dll
	;extension=php_oci8_11g.dll
	;extension=php_openssl.dll
	;extension=php_pdo_mysql.dll
	;extension=php_pdo_oci.dll
	;extension=php_pdo_odbc.dll
	;extension=php_pdo_pgsql.dll
	;extension=php_pdo_sqlite.dll
	;extension=php_pgsql.dll
	;extension=php_shmop.dll
	;extension=php_snmp.dll
	;extension=php_soap.dll
	;extension=php_sockets.dll
	;extension=php_sqlite3.dll
	;extension=php_sqlite.dll
	extension=php_tidy.dll
	extension=php_wincache.dll
	;extension=php_xmlrpc.dll
	;extension=php_xsl.dll

This is a minimal set of loaded extensions which will get you started.

If want to use **SQL Server** as a database, you will need to install the [SQL Server Driver for PHP](http://www.microsoft.com/downloads/en/details.aspx?displaylang=en&FamilyID=80e44913-24b4-4113-8807-caae6cf2ca05) and add an extension entry for it to the list above.

## Test PHP

  - Open **Command Prompt** and type the following:
	c:\php5\php.exe -v

You should see some output showing the PHP version. If you get something else, or nothing at all, then there are missing updates for your copy of Windows Server 2003. Open **Windows Update** and make sure you've updated everything including optional updates.

## Install SilverStripe

  - [Download SilverStripe](http://silverstripe.org/downloads)
  - Extract the download contents to **C:\Inetpub\wwwroot\silverstripe**
  - Open **inetmgr.exe**
  - Right click **Web Sites** and go to **New** > **Web Site**
  - Fill in all appropriate details. If you enter **(All Unassigned)** for the IP address field, make sure the port is something other than **80**, as this will conflict with "Default Web Site" in IIS. When asked for path, enter **C:\Inetpub\wwwroot\silverstripe**
  - Browse to **http://localhost:8888** or to the IP address you just assigned in your browser.

An installation screen should appear. There may be some permission problems, which you should be able to correct by assigning the **Users** group write permissions by right clicking files / folders in Windows Explorer and going to **Properties** then the **Security** tab.

When ready, hit **Install SilverStripe**.

SilverStripe should now be installed and you should have a basic site with three pages.

However, URLs will not look "nice", like this: http://localhost/index.php/about-us. In order to fix this problem, we need to install a third-party URL rewriting tool, as IIS 6 does not support this natively.

Proceed to **Install IIRF** below to enable nice URLs.

## Install IIRF

At the moment, all URLs will have index.php in them. This is because IIS does not support URL rewriting. To make this work, we need to install IIRF which is a third-party plugin for IIS.

  - [Download IIRF](http://iirf.codeplex.com/releases/view/36814) and install it
  - Create a new file called iirf.ini in C:\inetpub\wwwroot\silverstripe with this content
	RewriteEngine On
	MaxMatchCount 10
	IterationLimit 5
	# URLs with query strings 
	# Don't catch successful file references 
	RewriteCond %{REQUEST_FILENAME} !-f 
	RewriteRule ^(.*)\?(.+)$ /framework/main.php?url=$1&$2
	# URLs without query strings 
	RewriteCond %{REQUEST_FILENAME} !-f 
	RewriteRule ^(.*)$ /framework/main.php?url=$1

Friendly URLs should now be working when you browse to your site.

Remember that IIRF works on a per-virtual host basis. This means for each site you want IIRF to work for, you need to add a new entry to **Web Sites** in **inetmgr.exe**.

Thanks to **kcd** for the rules: [http://www.silverstripe.org/installing-silverstripe/show/10488#post294415](http://www.silverstripe.org/installing-silverstripe/show/10488#post294415)
