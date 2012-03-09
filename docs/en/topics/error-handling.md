# Error Handling

SilverStripe has its own error trapping and handling support.

## Error Levels

SilverStripe recognises two basic levels of error:

*  **WARNING:** Something strange has happened; the system has attempted to continue as best it can, but the developers
need to look at this.  This category also include areas where a newer version of SilverStripe requires changes to the
site's customised code.

*  **FATAL ERROR:** There is no way that the system can attempt to continue with the particular operation; it would be
dangerous to report success to the user.

You should use [user_error](http://www.php.net/user_error) to throw errors where appropriate.  The more information we
have about what's not right in the system, the better we can make the application.

*  **E_USER_WARNING:** Err on the side of over-reporting warnings.  The more warnings we have, the less chance there is
of a developer leaving a bug.  Throwing warnings provides a means of ensuring that developers know whow
    * Deprecated functions / usage patterns
    * Strange data formats
    * Things that will prevent an internal function from continuing.  Throw a warning and return null.

*  **E_USER_ERROR:** Throwing one of these errors is going to take down the production site.  So you should only throw
E_USER_ERROR if it's going to be **dangerous** or **impossible** to continue with the request.

Note that currently, the SilverStripe core doesn't follow these standards perfectly.

*  Right now, **every** failed SQL statement throws a fatal error.  Many 'select' queries could probably be reduced to
warnings.
*  A lot of assertion checking in the system that throws errors when it should throw warnings.

## Friendly Website Errors

An HTTP 500 error will be sent when there has been a fatal error on either a test or production site.  You can make this
friendlier - much like the 404 page, the error content can be edited within the CMS.

*  Create a page of type `[api:ErrorPage]`
*  Set the error code to 500
*  Publish the page.

**HOW IT WORKS: **The publication script for `[api:ErrorPage]` will write the full HTML content, including the template styling,
to assets/error-500.html.  The fatal error handler looks for the presence of this file, and if it exists, dumps the
content.  This means that database access isn't required to provide a 500 error page.

## Filesystem Logs



### From SilverStripe

You can indicate a log file relative to the site root. The named file will have a terse log sent to it, and the full log
(an encoded file containing backtraces and things) will go to a file of a similar name, but with the suffix ".full"
added.

`<mysite>/_config.php`:

	:::php
	// log errors and warnings
	SS_Log::add_writer(new SS_LogFileWriter('/my/logfile/path'), SS_Log::WARN, '<=');
	// or just errors
	SS_Log::add_writer(new SS_LogFileWriter('/my/logfile/path'), SS_Log::ERR);

### From PHP

In addition to SilverStripe-integrated logging, it is adviseable to fall back to PHPs native logging functionality. A
script might terminate before it reaches the SilverStripe errorhandling, for example in the case of a fatal error.

`<mysite>/_config.php`:

	:::php
	ini_set("log_errors", "On");
	ini_set("error_log", "/my/logfile/path");



## Email Logs

You can send both fatal errors and warnings in your code to a specified email-address.

`<mysite>/_config.php`:

	:::php
	// log errors and warnings
	SS_Log::add_writer(new SS_LogEmailWriter('admin@domain.com'), SS_Log::WARN, '<=');
	// or just errors
	SS_Log::add_writer(new SS_LogEmailWriter('admin@domain.com'), SS_Log::ERR);