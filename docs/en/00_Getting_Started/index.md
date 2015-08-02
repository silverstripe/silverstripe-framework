title: Getting Started
introduction: SilverStripe is a web application. This means that you will need to have a webserver and database. We will take you through the setup of the server environment as well the application itself.


## Installing SilverStripe

The best way to get SilverStripe is to [install with Composer](composer). Composer is a package management tool for PHP that
lets you install and upgrade SilverStripe and its modules.  Although installing Composer is one extra step, it will give you much more flexibility than just downloading the file from silverstripe.org.

Other ways to get SilverStripe:

 * If you just want to get the code as quickly as possible, you can [download SilverStripe from our website](http://www.silverstripe.org/software/download/).
 * If you already have an installed version of SilverStripe, and you haven't used Composer to get it, please see our [upgrading](/upgrading) guide.  Note that [Composer](composer) provides its own tools for upgrading.

## Setting up a server

### Linux/Unix

To run SilverStripe on Linux/Unix, set up one of the following web servers: 

*  [Install using Apache](installation) - our preferred platform
*  [Install using Lighttpd](installation/how_to/configure_lighttpd) - fast, but a bit trickier to get going
*  [Install using Nginx](installation/how_to/configure_nginx) - Super fast at serving static files. Great for large traffic sites.
*  [Install using nginx and HHVM](installation/how_to/setup_nginx_and_hhvm) - nginx and [HHVM](http://hhvm.com/) as a faster alternative to PHP.

### Windows

The most straightforward way to get SilverStripe running on Windows is with the [Microsoft Web Platform installer](installation/other_installation_options/windows_platform_installer).  You can skip the "getting the code" step.

For more flexibility, you can set up either of the following web servers, and use Composer to get the code:

 * [Install using IIS](installation/other_installation_options/windows_iis7)
 * [Install using Apache/WAMP](installation/windows)

### Mac OS X

Mac OS X comes with a built-in webserver, but there are a number of other options:

 * [Install using MAMP](installation/mac_osx)
 * [Install using Homebrew](installation/other_installation_options/mac_osx_homebrew)

### Virtual Machines through Vagrant

[Vagrant](https://www.vagrantup.com/) creates portable development environments
which can be hosted on Linux, Windows and Mac OS X. The virtual machine
usually runs a flavour of Linux. As a self-contained pre-configured environment,
getting up an running with Vagrant tends to be easier than creating a complete
development environment from scratch on your own machine.

 * [silverstripe-australia/vagrant-environment](https://github.com/silverstripe-australia/vagrant-environment)
 * [BetterBrief/vagrant-skeleton](https://github.com/BetterBrief/vagrant-skeleton)

Note: These instructions are supported by the community.

## Virtual Machines through Bitnami

[Bitnami](https://bitnami.com) is an online service that makes it easy to get
apps running on cloud providers like Amazon Web Services as well as local
virtualised environments. Bitnami has a [SilverStripe Virtual Machine](https://bitnami.com/stack/silverstripe/virtual-machine)
ready for download or installation on a cloud platform.

## Troubleshooting

If you run into trouble, see [common-problems](installation/common_problems) or post to the 
[SilverStripe forums](http://silverstripe.org/community/forums/).

## Related

 * [Module installation](/developer_guides/extending/modules)
