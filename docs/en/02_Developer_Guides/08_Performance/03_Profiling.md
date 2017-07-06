title: Profiling
summary: Identify bottlenecks within your application.

# Profiling

Profiling is the best way to identify bottle necks and other slow moving parts of your application prime for
optimization.

SilverStripe does not include any profiling tools out of the box, but we recommend the use of existing tools such as
[XHProf](https://github.com/facebook/xhprof/), [XDebug](http://xdebug.org/) and [SilverStripe DebugBar](https://github.com/lekoala/silverstripe-debugbar).

* [Profiling with XHProf](http://techportal.inviqa.com/2009/12/01/profiling-with-xhprof/)
* [Profiling PHP Applications With XDebug](http://devzone.zend.com/1139/profiling-php-applications-with-xdebug/)

## Profiling with SilverStripe DebugBar

The [SilverStripe DebugBar](https://github.com/lekoala/silverstripe-debugbar) module will allow developers to profile SilverStripe page execution, database queries, `SS_Log` messages, requirements, template use and environment settings from within a toolbar at the bottom of a page.

It can help developers to identify bottlenecks in an application, duplicated or slow running database queries and pages that are taking longer than a configurable amount of time to load.

For more information on this module, see [SilverStripe DebugBar on GitHub](https://github.com/lekoala/silverstripe-debugbar).
