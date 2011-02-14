# Live Query ChangeLog

## 1.1.1

* Compatibility fix with 1.4.1 (thanks)
* Updated README

## 1.1

* Updated to better integrate with jQuery 1.3 (no longer supports jQuery < 1.3)

## 1.0.3

* LiveQueries are run as soon as they are created to avoid potential flash of content issues

## 1.0.2

* Updated to work with jQuery 1.2.2

## 1.0.1

* Added removeAttr, toggleClass, emtpy and remove to the list of registered core DOM manipulation methods
* Removed setInterval in favor of on-demand setTimeout
* Calling livequery with the same arguments (function references), restarts the existing Live Query instead of creating a new one