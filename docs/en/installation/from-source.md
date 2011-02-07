# Installation from Source Control #

For getting a project up and running with a release, you are typically best off
with the official [silverstripe.org/download](http://silverstripe.org/download). If you want to get the get the "latest and greatest" pre-release code (either
on a release brank, or on "trunk"), you need to use our version control.

We also require you to use this method for any [patch contributions](/misc/contributing),
to ensure you're working on the latest codebase, and the problem you're looking at
is not already fixed.

## SilverStripe Core ##

SilverStripe core is currently hosted on Subversion at [svn.silverstripe.org](http://svn.silverstripe.org).
You can get subversion clients for any operating system, see the [subversion website](http://subversion.tigris.org).

SilverStripe projects are created by combining the "cms" and "sapphire"
modules along with any other modules that your site might need. 

These modules are prepackaged in a "phpinstaller" project through [svn:externals](http://svnbook.red-bean.com/en/1.5/svn.advanced.externals.html).

To check out the installer project, use one of the following commands:

	# Check out the latest release branch
	svn checkout http://svn.silverstripe.org/open/phpinstaller/branches/2.4
	
	# Check out trunk
	svn checkout http://svn.silverstripe.org/open/phpinstaller/trunk

<div class="hint" markdown="1">
Please note that you will need Subversion 1.5.0 or greater
</div>

## Other Modules ##

Modules listed on [silverstripe.org/modules](http://silverstripe.org/modules) can be hosted
in any version control system (typically subversion or git). Please read the module
page for source code locations and installation instructions. The general process of
[module installation](/topics/modules) is documented as well.

A good place to start looking for the source code of popular modules are the [github.com/silverstripe](http://github.com/silverstripe)
and [github.com/silverstripe-labs](http://github.com/silverstripe-labs) project pages.

## Related ##

 * [Contributing: Submitting patches](/misc/contributing)