# Debugging

## Environment Types

Silverstripe knows three different environment-types (or "debug-levels"). Each of the levels gives you different tools
and functionality. "dev", "test" and "live". You can either configure the environment of the site in the
mysite/_config.php file or in your [environment configuration file](/topics/environment-management).

The definition of setting an environment in your mysite/_config.php looks like

	:::php
	Director::set_environment_type("dev");

### Dev Mode

When developing your websites, adding page types or installing modules you should run your site in devmode. In this mode
you will be able to view full error backtraces and view the development tools without logging in as admin.

To set your site to dev mode set this in your mysite/_config.php file

	:::php
	Director::set_environment_type("dev");


Please note **devmode should not be enabled long term on live sites for security reasons**. In devmode by outputting
backtraces of function calls a hacker can gain information about your environment (including passwords) so you should
use devmode on a public server very very carefully


### Test Mode

Test mode is designed for staging environments or other private collaboration sites before deploying a site live. You do
not need to use test mode if you do not have a staging environment or a place for testing which is on a public server)

In this mode error messages are hidden from the user and it includes `[api:BasicAuth]` integration if you want to password
protect the site.

To set your site to test mode set this in your `mysite/_config.php` file

	:::php
	Director::set_environment_type("test");


A common situation is to enable password protected site viewing on your test site only. You can enable that but adding
this to your `mysite/_config` file

	:::php
	if(Director::isTest()) BasicAuth::protect_entire_site();


### Live Mode

Live sites should always run in live mode. Error messages are suppressed from the user but can be optionally configured
to email the developers. This enables near real time reporting of any fatal errors or warnings on the site and can help
find any bugs users run into.

To set your site to live mode set this in your `mysite/_config.php` file

	:::php
	Director::set_environment_type("live");



### Checking Environment Types

Use the following methods:

	:::php
	Director::isDev();
	Director::isTest();
	Director::isLive();


This is useful when you have various API keys on your site and separate ones for dev / live or for configuring
environment settings based on type 

	:::php
	if(Director::isDev()) {
	// this is for dev only
	}
	else {
	// this is for the live site
	}


## Email Errors

	:::php
	if(Director::isLive()) Debug::send_errors_to("your@email.com");


## Customizing Error-Output

You can customize "friendly error messages" in test/live-mode by creating *assets/error-500.html*.

## URL Variable Tools

You can get lots of information on the current rendering context without writing any code or launching a debugger: Just
attach some [Debug Parameters](/reference/urlvariabletools) to your current URL to see the compiled template, or all performed
SQL-queries.

## Debugging methods

The Debug class contains a number of static methods

*  *Debug::show($myVariable)*: performs a kind of *print_r($myVariable)*, but shows it in a more useful format.
*  *Debug::message("Wow, that's great")*: prints a short debugging message.
*  *SS_Backtrace::backtrace()*: prints a calls-stack
*  *Debug::send_errors_to("sam@silverstripe.com")*: All errors will get sent to this address.

### Error handling

On development sites, we deal harshly with any warnings or errors: a full call-stack is shown and execution stops.  This
is basically so that we deal with them promptly, since most warnings are indication that **something** is broken.

On live sites, all errors are emailed to the address specified in Debug::sendLiveErrorsTo($email)

### Debugging techniques

Since we don't have a decent interactive debugger going, we use the following debugging techniques:

*  Putting *Debug::show()* and *Debug::message()* at key places in the code can help you know what's going on. 
Sometimes, it helps to put this debugging information into the core modules, although, if possible, try and get what you
need by using [url querystring variables](/reference/urlvariabletools).

*  Calling *user_error("breakpoint", E_USER_ERROR)* will kill execution at that point and give you a call stack to see
where you came from.  Alternatively, *SS_Backtrace::backtrace()* gives you similar information without killing
execution.

*  There are some special [url querystring variables](/reference/urlvariabletools) that can be helpful in seeing what's going on
with core modules, such as the templates.

*  You can also use *$Debug* with *ViewableData* in templates.

#### Unit Testing

A good way to avoid writing the same test stubs and var_dump() commands over and over again is to codify them as [unit
tests](testing-guide). This way you integrate the debugging process right into your quality control, and eventually in
the development effort itself as "test-driven development".

#### Profiling

Silverstripe includes a profiling suite called [Profiler](http://www.adepteo.net/profiler/manual.html) from Carl Taylor
at Adepteo.  You can use this withing your installation during development to find bottlenecks and more. You can enable
the profiler by adding `?debug_profile=1` to your URL.