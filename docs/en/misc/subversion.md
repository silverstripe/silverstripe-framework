## Subversion

Subversion [website](http://subversion.tigris.org) is a **version control system**.
You can browse our Subversion "code repository" [here](http://open.silverstripe.com/browser), however we now use GIT to 
manage our modules (see [Contributing](contributing)).

This page only describes SilverStripe-specific information on how to handle subversion. For a general introduction,
please read the [Official Subversion Book](http://svnbook.red-bean.com/) (available free online).

Start with the ["Basic Usage" chapter](http://svnbook.red-bean.com/en/1.5/svn.tour.html).

### Clients
Make sure you have an updated [subversion client](http://subversion.tigris.org/links.html#clients) installed.
Subversion 1.5 or higher is required for features such as relative externals and merge tracking.

### Checkout / Download
See [Download SilverStripe](http://silverstripe.org/download) and the
["Update your working copy" chapter](http://svnbook.red-bean.com/en/1.5/svn.tour.cycle.html#svn.tour.cycle.update).

### Committing
The SilverStripe core and modules require permission to commit code. 
Please have a look at our [contributors guidelines](contributing) to find out how you can gain access.

### Configuring subversion for correct newline handling
You should configure your subversion client to set the ''svn:eol-style'' property to ''native'' on all text files checked into the system.  This will ensure that we don't run into troubles, getting non-unix newlines inside our repository.

To do this, edit your ''~/.subversion/config'' file on your development machine, and add the following settings.  Note that if you already have ''[miscellany]'' and ''[auto-props]'' sections, you should combine these settings with the existing sections, rather than doing a blind copy & paste to the end of the file.

	:::php
	[miscellany]
	enable-auto-props = yes

	// Section for configuring automatic properties.
	[auto-props]
	*.js = svn:eol-style=native
	*.html = svn:eol-style=native
	*.inc = svn:eol-style=native
	*.css = svn:eol-style=native
	*.php = svn:eol-style=native
	*.xml = svn:eol-style=native
	*.csv = svn:eol-style=native
	*.htm = svn:eol-style=native
	*.ini = svn:eol-style=native
	*.json = svn:eol-style=native
	*.php5 = svn:eol-style=native
	*.py = svn:eol-style=native
	*.ss = svn:eol-style=native
	*.yml = svn:eol-style=native
	*.yaml = svn:eol-style=native
	*.xhtml = svn:eol-style=native

Note that if the repository gets out of whack, the following commands run on a linux box will correct things in the current working copy:

	find | grep -v \._ | \
	grep "\.\(js\|php\|css\|inc\|html\|html\|php5\|py\|json\|ini\|xml\|csv\)"\$ | \
	xargs svn propset svn:eol-style native
	
	find | grep -v \._ | \
	grep "\.\(js\|php\|css\|inc\|html\|html\|php5\|py\|json\|ini\|xml\|csv\)"\$ | \
	xargs dos2unix

### Feature Branches
For more complicated bugfixes or longer-term development, you may want to create a Feature Branch. For example, you might want
to add support for other rich-text-editors within the CMS - a complex task which can't be contained in a single patch.
Feature branches are a copy of trunk, and usually have a short lifetime in which active development happens. 
**The feature branch maintainer is responsible for keeping his branch in sync with trunk and reintegrate when development 
is complete.**

More information about ["Feature Branches"](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.commonpatterns.html#svn.branchmerge.commonpatterns.feature), 
[merging changes](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.html) and [resolving conflicts](http://svnbook.red-bean.com/en/1.5/svn.tour.cycle.html#svn.tour.cycle.resolve).

#### Example: The 'nestedurls' branch
Example for a feature branch for the ''sapphire'' module called ''nestedurls''.

Creating the branch is a simple matter of running the ''svn cp'' command (see [SVN Book: "Creating a Branch"](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.using.html#svn.branchmerge.using.create)).

	svn cp http://svn.silverstripe.com/open/modules/sapphire/trunk http://svn.silverstripe.com/open/modules/sapphire/branches/nestedurls


After creating a feature branch, you commit your changes as usual, but also merge in any new changes from trunk 
(see [SVN Book: "Keeping your Branch in Sync"](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.basicmerging.html#svn.branchemerge.basicmerging.stayinsync)).

	cd /your/nestedurls/working/copy
	svn merge http://svn.silverstripe.com/open/modules/sapphire/trunk

Once you've finished your feature development (usually a timeframe around 4-8 weeks), you "reintegrate" your branch with the
trunk repository. This activity happens only once, and the feature branch has to be removed afterwards.

	cd /your/trunk/working/copy
	svn merge --reintegrate http://svn.silverstripe.com/open/modules/sapphire/branches/nestedurls

You can get information about the merge process (see 
[SVN Book: "Mergeinfo and Previews"](http://svnbook.red-bean.com/en/1.5/svn.branchmerge.basicmerging.html#svn.branchmerge.basicmerging.mergeinfo)).

	cd /your/nestedurls/working/copy
	# revisions which are you still have to merge from trunk to your branch
	svn mergeinfo http://svn.silverstripe.com/open/modules/sapphire/trunk --show-revs eligible
	# revisions which are already merged from trunk to your branch
	svn mergeinfo http://svn.silverstripe.com/open/modules/sapphire/trunk

### Troubleshooting

#### SVN for your own websites

Here is a step-by-step guide on how to work with SVN on your own site.  It oversimplifies some aspects, but it is a good
introduction. NOTE: you will need SSH and SVN installed and available on your server.

*  Install LAMP / WAMP and an SVN application (Tortoise SVN on Windows)
*  Buy an SVN repository: http://www.SVNrepository.com/  OR set one up on your own server or look at freeby alternatives.
*  Go to your SVN repository server and create a new repository - I would recommend to name it after the site you are about to setup, e.g. myfirstsite.com.
*  Create / go to web folder on local LAMP/WAMP
*  SVN "checkout" your empty repository (e.g. http://sunny.SVNrepository.com/SVN/myaccountname/myfirstsite.com).
*  SVN "propedit" SVN:externals, add the following SVN property to the root directory:

SVN:externals (also add your own general modules here, etc... etc... this should be a long list):

	cms http://SVN.silverstripe.com/open/modules/cms/branches/2.4
	sapphire http://SVN.silverstripe.com/open/modules/sapphire/branches/2.4

*  SVN "update" your project. All the various files should be imported now.
*  SVN "commit" your externals. 
*  In the root directory, create the following files: _ss_environment.php.sample, .htacess.sample and "commit" these to your repository.
*  Copy in local LAMP / WAMP environment: _ss_environment.php.sample to _ss_environment.php; and .htacess.sample to .htacess
*  Make sure that .htaccess and _ss_environment.php files are excluded from any commits.  This is another SVN property attached to the root directory: 

	SVN:ignore:
	.htaccess
	_ss_environment.php
	assets

*  Create assets folder in root directory
*  Your site should now be ready on your local WAMP / LAMP.  You can now create a mysite and themes folder, add all your files, edit, bla bla until your site is ready. Then SVN "add" and SVN "commit" all your files to your repository (usually, you do many commits)
*  Once you have created the site locally and committed it to your repository you should SSH to your web server (recommended application for windows is PUTTY)
*  Once logged in to your web server, browse to the root web folder 
*  Make sure SVN is installed on the server (just type SVN help or something to check).
*  SSH prompt, type: 

	SVN checkout http://sunny.SVNrepository.com/SVN/myaccountname/myfirstsite.com . 

<div class="hint" markdown="1">
Add a DOT at the end to check out files to current directory
</div>

*  You should now have all your files on your webserver
*  Copy on the server (using SSH)

	cp _ss_environment.php.sample  _ss_environment.php
	cp .htacess.sample  .htacess

*  Edit these files: _ss_environment.php.sample , .htacess.sample, using the following SSH commands (if the nano application is not available then try pico):

	nano _ss_environment.php.sample 
	nano .htacess.sample 

*  Create a folder called assets and make sure permissions are set correctly
*  Website should now be up and running. (run dev/build to start). 

#### A few point additional points:
* The whole concept of tags and branches have been left out, just to simplify.  
* We have also left out the idea of a test and live site.  The instructions here can be used to setup a test site.  Once you are happy with a test site you can move the code to a live site using the SVN "merge" command. In principle, this is how it works: open SSH and go to the root directory of the live site. Then type:

	svn merge http://mysvnrepository/branches/live/ http://mysvnrepository/branches/test/

* If you want to update a repository, but you want the repository on the webserver to be locked to a version then you need set the svn:externals as follows:

	cms -r1234567 http://svn.silverstripe.com/open/modules/cms/branches/2.4

where 1234567 is the revision of your repository that you want to use

* If you want to get a better understanding of what is going on in a repository then use the following SVN commands: SVN "status" and SVN "info". 
* You can not and should not make any changes to any of the core modules and other modules added with svn:externals

### Related
* [contributing](contributing)
* [release process](release-process)