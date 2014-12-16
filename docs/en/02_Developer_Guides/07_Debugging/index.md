summary: Learn how to identify errors in your application and best practice for logging application errors.

# Debugging

SilverStripe can be a large and complex framework to debug, but there are ways to make debugging less painful. In this
guide we show the basics on defining the correct [Environment Type](environment_type) for your application and other
built-in helpers for dealing with application errors.

[CHILDREN]

## Performance

See the [Profiling](../performance/profiling) documentation for more information on profiling SilverStripe to track down
bottle-necks and identify slow moving parts of your application chain.

## Debugging Utilities

The [api:Debug] class contains a number of static utility methods for more advanced debugging.

	:::php
	Debug::show($myVariable);
	// similar to print_r($myVariable) but shows it in a more useful format.

	Debug::message("Wow, that's great");
	// prints a short debugging message.

	SS_Backtrace::backtrace();
	// prints a calls-stack

## API Documentation

* [api:SS_Log]
* [api:SS_Backtrace]
* [api:Debug]