Jasmine Adapter for [JsTestDriver][jstd]
========================================

Author
------

* Olmo Maldonado (me@ibolmo.com)
* Misko Hevery (misko@hevery.com)
* Christoph Pojer (christoph.pojer@gmail.com)

Requirements
------------

 - [JsTestDriver (JSTD)][jstd]
 - [Jasmine][jasmine]

Usage
-----

Create, or update, a `jstestdriver.conf` file (see [wiki page][jstd-conf] for more info).

Update your `jstestdriver.conf` by prepending the jasmine library and the adapter's source files.

For example:

	load:
    - "../jasmine/lib/jasmine-0.10.0.js"
    - "../JasmineAdapter/src/*"
    - "your_source_files.js"
    - "your_test_files.js"

Copy `server.sh` and `test.sh` (included) to your working directory, for convenience.

	# copy
	cp /path/to/jasmine-jstestdriver-adapter/*.sh ./
	
First: run `server.sh` and supply `-p`, for port, and `-j`, path to `jstestdriver.jar` or follow the convention defined in the `.sh` scripts (see Caveats below).

Open up [http://localhost:9876/capture](http://localhost:9876/capture) (update for your port) in any browser.

Finally: run `test.sh` to test all tests (specs) included with the `jstestdriver.conf`. Optionally pass a `-j` and `-t` arguments to `test.sh` to set the path to `jstestdriver.jar` and any test you'd only like to run, respectively.


Directory Layout
----------------
 
 - src: The adapter source code. Intent is to match interface with interface.
 - src-test: The test files that verifies that the adapter works as intended.

Caveats
-------

### jsTestDriver.conf and *.sh files

The files located in this repo assume that the parent folder has the jasmine source and a jstestdriver compiled available.

Update the paths, or pass arguments (as explained above), to reflect your own layout if you'd like to test the adapter.


[jstd]: http://code.google.com/p/js-test-driver
[jstd-conf]: http://code.google.com/p/js-test-driver/wiki/ConfigurationFile
[jasmine]: http://github.com/pivotal/jasmine
