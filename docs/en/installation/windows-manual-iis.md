# Manual installation on Windows using IIS

Install SilverStripe manually on Windows using IIS as the web server.

If you are not confident in installing web server software manually on Windows, it is recommended you use the
[Web Platform Installer](windows-pi) method instead, which will do the installation for you.

## [Install using IIS 7.x](windows-manual-iis-7)

This applies to Windows Server 2008, Windows Server 2008 R2, Windows Vista, and Windows 7.

## [Install using IIS 6.x](windows-manual-iis-6)

*Note: It's recommended you upgrade to Windows Server 2008 R2 which uses IIS 7.5*.

This applies to Windows Server 2003 and Windows Server 2003 R2.

## Additional notes

Microsoft has no URL rewriting module for anything less than IIS 7.x. This will mean your URLs are like yoursite.com/index.php/about-us rather than yoursite.com/about-us.
However, if you do want friendly URLs you must you must buy or use other URL rewriting software: 

 * [IIRF](http://iirf.codeplex.com/) (should work for most cases - see [IIS 6 guide](windows-manual-iis-6) for rewrite rules)
 * [ISAPI_Rewrite](http://www.helicontech.com/download-isapi_rewrite3.htm) (The freeware, lite version should be fine for simple installations)
 * If you have 64-bit Windows, you can try [this](http://www.micronovae.com/ModRewrite/ModRewrite.html)

Instructions are available for [installing PHP on IIS 6](http://learn.iis.net/page.aspx/248/configuring-fastcgi-extension-for-iis60/) by the IIS team.

On Windows XP, you need to disable **Check that file exists**. See [installation-on-windows-pi](windows-pi) for more information.

Matthew Poole has expanded on these instructions [with a tutorial](http://cubiksoundz.blogspot.com/2008/12/tech-note-installing-silverstripe-cms.html).
