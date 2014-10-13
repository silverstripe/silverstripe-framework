title: Error Handling
summary: Trap, fire and report user exceptions, warnings and errors.

# Error Handling

SilverStripe has its own error trapping and handling support. On development sites, SilverStripe will deal harshly with 
any warnings or errors: a full call-stack is shown and execution stops for anything, giving you early warning of a 
potential issue to handle.

## Triggering the error handler.

You should use [user_error](http://www.php.net/user_error) to throw errors where appropriate.

	:::php
	if(true == false) {
		user_error("I have an error problem", E_USER_ERROR);
	}

	if(0 / 0) {
		user_error("This time I am warning you", E_USER_WARNING);
	}

## Error Levels

*  **E_USER_WARNING:** Err on the side of over-reporting warnings. Throwing warnings provides a means of ensuring that 
developers know:
    * Deprecated functions / usage patterns
    * Strange data formats
    * Things that will prevent an internal function from continuing.  Throw a warning and return null.

*  **E_USER_ERROR:** Throwing one of these errors is going to take down the production site.  So you should only throw
E_USER_ERROR if it's going to be **dangerous** or **impossible** to continue with the request.


## Filesystem Logs

You can indicate a log file relative to the site root.

**mysite/_config.php**

	:::php
	if(!Director::isDev()) {
		// log errors and warnings
		SS_Log::add_writer(new SS_LogFileWriter('/my/logfile/path'), SS_Log::WARN, '<=');

		// or just errors
		SS_Log::add_writer(new SS_LogFileWriter('/my/logfile/path'), SS_Log::ERR);
	}

<div class="info" markdown="1">
In addition to SilverStripe-integrated logging, it is advisable to fall back to PHPs native logging functionality. A
script might terminate before it reaches the SilverStripe error handling, for example in the case of a fatal error. Make
sure `log_errors` and `error_log` in your PHP ini file are configured.
</div>

## Email Logs

You can send both fatal errors and warnings in your code to a specified email-address.

**mysite/_config.php**

	:::php
	if(!Director::isDev()) {
		// log errors and warnings
		SS_Log::add_writer(new SS_LogEmailWriter('admin@domain.com'), SS_Log::WARN, '<=');

		// or just errors
		SS_Log::add_writer(new SS_LogEmailWriter('admin@domain.com'), SS_Log::ERR);
	}

## API Documentation

* [api:SS_Log]