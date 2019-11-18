---
title: Command Line Interface
summary: Automate SilverStripe, run Cron Jobs or sync with other platforms through the Command Line Interface.
introduction: Automate SilverStripe, run Cron Jobs or sync with other platforms through the Command Line Interface.
icon: terminal
---

SilverStripe can call [Controllers](../controllers) through a command line interface (CLI) just as easily as through a
web browser. This functionality can be used to automate tasks with cron jobs, run unit tests, or anything else that
needs to interface over the command line.

The main entry point for any command line execution is `cli-script.php` in the framework module.
For example, to run a database rebuild from the command line, use this command:

```bash
cd your-webroot/
php vendor/silverstripe/framework/cli-script.php dev/build
```

[notice]
Your command line php version is likely to use a different configuration as your webserver (run `php -i` to find out
more). This can be a good thing, your CLI can be configured to use higher memory limits than you would want your website
to have.
[/notice]

## Sake - SilverStripe Make

Sake is a simple wrapper around `cli-script.php`. It also tries to detect which `php` executable to use if more than one
are available. It is accessible via `vendor/bin/sake`.

[info]
If you are using a Debian server: Check you have the php-cli package installed for sake to work. If you get an error
when running the command php -v, then you may not have php-cli installed so sake won't work.
[/info]

### Installation

`sake` can be invoked using `./vendor/bin/sake`. For easier access, copy the `sake` file into `/usr/bin/sake`.

```
cd your-webroot/
sudo ./vendor/bin/sake installsake
```

[warning]
This currently only works on UNIX like systems, not on Windows.
[/warning]

### Configuration

Sometimes SilverStripe needs to know the URL of your site. For example, when sending an email or generating static
files. When you're visiting the site in a web browser this is easy to work out, but when executing scripts on the
command line, it has no way of knowing.

You can use the `SS_BASE_URL` environment variable to specify this.

```
SS_BASE_URL="http://localhost/base-url"
```

### Usage

`sake` can run any controller by passing the relative URL to that controller.


```bash
sake /
# returns the homepage

sake dev/
# shows a list of development operations
```

`sake` is particularly useful for running build tasks.

```bash
sake dev/build "flush=1"
```

[alert]
You have to run "sake" with the same system user that runs your web server,
otherwise "flush" won't be able to clean the cache properly.
[/alert]

It can also be handy if you have a long running script..

```bash
sake dev/tasks/MyReallyLongTask
```

### Running processes

`sake` can be used to make daemon processes for your application.

Make a task or controller class that runs a loop. To avoid memory leaks, you should make the PHP process exit when it
hits some reasonable memory limit. Sake will automatically restart your process whenever it exits.

Include some appropriate `sleep()`s so that your process doesn't hog the system. The best thing to do is to have a short
sleep when the process is in the middle of doing things, and a long sleep when doesn't have anything to do.

This code provides a good template:


```php
use SilverStripe\Control\Controller;

class MyProcess extends Controller
{

    private static $allowed_actions = [
        'index'
    ];

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
```

Then the process can be managed through `sake`

```bash
sake -start MyProcess
sake -stop MyProcess
```

[notice]
`sake` stores `pid` and log files in the site root directory.
[/notice]

## Arguments

Parameters can be added to the command. All parameters will be available in `$_GET` array on the server.

```bash
cd your-webroot/
php vendor/silverstripe/framework/cli-script.php myurl myparam=1 myotherparam=2
```

Or if you're using `sake`

```bash
vendor/bin/sake myurl "myparam=1&myotherparam=2"
```

## Running Regular Tasks With Cron

On a UNIX machine, you can typically run a scheduled task with a [cron job](http://en.wikipedia.org/wiki/Cron). Run
`BuildTask` in SilverStripe as a cron job using `sake`.

The following will run `MyTask` every minute.

```bash
* * * * * /your/site/folder/vendor/bin/sake dev/tasks/MyTask
```
