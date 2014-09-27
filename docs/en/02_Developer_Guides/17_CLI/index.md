summary: Automate SilverStripe, run Cron Jobs or sync with other platforms through the Command Line Interface.

# Command Line

SilverStripe can call [controllers](../controllers) through a command line interface (CLI) just as easily as through a 
web browser. This can be used to automate tasks with cron jobs, run unit tests, or anything else that needs to interface
over the command line.

The main entry point for any command line execution is `cli-script.php`. For example, to run a database rebuild
from the command line, use this command:
	
	:::bash
	cd your-webroot/
	php framework/cli-script.php dev/build

<div class="notice">
Your command line php version is likely to use a different configuration as your webserver (run `php -i` to find out 
more). This can be a good thing, your CLI can be configured to use higher memory limits than you would want your website
to have.
</div>

## Sake: SilverStripe Make

Sake is a simple wrapper around `cli-script.php`. It also tries to detect which `php` executable to use if more than one 
are available.

<div class="info" markdown='1'>
If you are using a debian server: Check you have the php-cli package installed for sake to work. If you get an error 
when running the command php -v, then you may not have php-cli installed so sake won't work.
</div>

### Installation

You can copy the `sake` file into `/usr/bin/sake` for easier access (this is optional):

	cd your-webroot/
	sudo ./framework/sake installsake

<div class="warning">
This currently only works on UNIX-like systems, not on Windows.
</div>

### Configuration

Sometimes SilverStripe needs to know the URL of your site, for example, when sending an email or generating static 
files. When you're visiting your site in a web browser this is easy to work out, but if you're executing scripts on the 
command line, it has no way of knowing.

To work this out, you should add lines of this form to your [_ss_environment.php](/getting_started/environment_management) 
file.

	:::php
	global $_FILE_TO_URL_MAPPING;

	$_FILE_TO_URL_MAPPING['/Users/sminnee/Sites'] = 'http://localhost';

The above statement tells SilverStripe that anything executed under the `/Users/sminnee/Sites` directly will have the
base URL `http://localhost`.

You can add multiple file to url mapping definitions. The most specific mapping will be used. For example:

	:::php
	global $_FILE_TO_URL_MAPPING;

	$_FILE_TO_URL_MAPPING['/Users/sminnee/Sites'] = 'http://localhost';
	$_FILE_TO_URL_MAPPING['/Users/sminnee/Sites/mysite'] = 'http://mysite.localhost';

### Usage

Sake is particularly useful for running build tasks
	
	:::bash
	cd /your/site/folder
	sake dev/build "flush=1"
	sake dev/tests/all

It can also be handy if you have a long running script.
	
	:::bash
	cd /your/site/folder
	sake dev/tasks/MyReallyLongTask

### Running processes

You can use sake to make daemon processes for your application.

Step 1: Make a task or controller class that runs a loop. To avoid memory leaks, you should make the PHP process exit 
when it hits some reasonable memory limit. Sake will automatically restart your process whenever it exits.

Step 2: Include some appropriate sleep()s so that your process doesn't hog the system.  The best thing to do is to have 
a short sleep when the process is in the middle of doing things, and a long sleep when doesn't have anything to do.

This code provides a good template:

	:::php
	<?php

	class MyProcess extends Controller {

		private static $allowed_actions = array(
			'index'
		);

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

Step 3: Install the "daemon" command-line tool on your server.

Step 4: Use sake to start and stop your process

	:::bash
	sake -start MyProcess
	sake -stop MyProcess

<div class="notice">
Sake stores Pid and log files in the site root directory.
</div>

## GET parameters as arguments

You can add parameters to the command by using normal form encoding. All parameters will be available in `$_GET` within 
SilverStripe. Using the `cli-script.php` directly:

	:::bash
	cd your-webroot/
	php framework/cli-script.php myurl myparam=1 myotherparam=2

Or if you're using `sake`

	:::bash
	sake myurl "myparam=1&myotherparam=2"

## Running Regular Tasks With Cron

On a UNIX machine, you can typically run a scheduled task with a [cron job](http://en.wikipedia.org/wiki/Cron). You can
execute any `BuildTask` in SilverStripe as a cron job using `Sake`.

	:::bash
	* * * * * /your/site/folder/sake dev/tasks/MyTask
