# Commandline Usage via "sake"

## Introduction

SilverStripe can call controllers through commandline `php` just as easily as through a web browser.
This can be handy to automate tasks with cron jobs, run unit tests and maintenance tasks,
and a whole bunch of other scripted goodness.

The main entry point for any commandline execution is `cli-script.php`. For example, to run a database rebuild
from the commandline, use this command:

	cd your-webroot/
	php sapphire/cli-script.php dev/build

Make sure that your commandline php version uses the same configuration as your webserver (run `php -i` to find out more).

## GET parameters as arguments

You can add parameters to the command by using normal form encoding.
All parameters will be available in `$_GET` within SilverStripe.

	cd your-webroot/
	php sapphire/cli-script.php myurl myparam=1 myotherparam=2

## SAKE: Sapphire make

Sake is a simple wrapper around `cli-script.php`. It also tries to detect which `php` executable to use
if more than one are available.

**If you are using a debian server:** Check you have the php-cli package installed for sake to work. 
If you get an error when running the command php -v, then you may not have php-cli installed so sake won't work.

### Installation

You can copy the `sake` file into `/usr/bin/sake` for easier access (this is optional):

	cd your-webroot/
	sudo ./sapphire/sake installsake

Note: This currently only works on unix-like systems, not on Windows.

## Configuration

Sometimes SilverStripe needs to know the URL of your site, for example, when sending an email.  When you're visiting
your site in a web browser this is easy to work out, but if you're executing scripts on the command-line, it has no way
of knowing.

To work this out, you should add lines of this form to your [_ss_environment.php](/topics/environment-management) file.

	:::php
	global $_FILE_TO_URL_MAPPING;
	$_FILE_TO_URL_MAPPING['/Users/sminnee/Sites'] = 'http://localhost';


What the line says is that any Folder under /Users/sminnee/Sites/ can be accessed in a web browser from
http://localhost.  For example, /Users/sminnee/Sites/mysite will be available at http://localhost/mysite.

You can add multiple file to url mapping definitions.  The most specific mapping will be used. For example:

	:::php
	global $_FILE_TO_URL_MAPPING;
	$_FILE_TO_URL_MAPPING['/Users/sminnee/Sites'] = 'http://localhost';
	$_FILE_TO_URL_MAPPING['/Users/sminnee/Sites/mysite'] = 'http://mysite.localhost';


Using this example, /Users/sminnee/Sites/mysite/ would be accessed at http://mysite.localhost/, and
/Users/sminnee/Sites/othersite/ would be accessed at http://localhost/othersite/

## Usage

Sake will either run `./sapphire/cli-script.php` or `./cli-script.php`, depending on what's available.

It's particularly useful for running build tasks...

	cd /your/site/folder
	sake db/build
	sake dev/tests/all


It can also be handy if you have a long running script.

	cd /your/site/folder
	sake MyReallyLongTask


### Running processes

You can use sake to make daemon processes for your application.

Step 1: Make a task or controller class that runs a loop.  Because Sapphire has memory leaks, you should make the PHP
process exit when it hits some reasonable memory limit.  Sake will automatically restart your process whenever it exits.

The other thing you should do is include some appropriate sleep()s so that your process doesn't hog the system.  The
best thing to do is to have a short sleep when the process is in the middle of doing things, and a long sleep when
doesn't have anything to do.

This code provides a good template:

	:::php
	class MyProcess extends Controller {
		public static $allowed_actions = array('index');
	  function index() {
	    set_time_limit(0);
	    while(memory_get_usage() < 32*1024*1024) {
	      if($this->somethingToDo()) {
	        $this->doSomething();
	        sleep(1)
	      } else {
	        sleep(300);
	      }
	    }
	  }
	}



Step 2: Install the "daemon" command-line tool on your server.

Step 3: Use sake to start and stop your process

	sake -start MyProcess
	sake -stop MyProcess


Note that sake processes are currently a little brittle, in that the pid and log files are placed in the site root
directory, rather than somewhere sensible like /var/log or /var/run.