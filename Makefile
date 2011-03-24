#
# This makefile is a secondary way of installing SilverStripe.
# It is used for things like continuous integration
#
# Most users should simply visit the site root in your web browser.
#
#
URL=`php ./cli-script.php SapphireInfo/baseurl`

test: phpunit

phpunit:
	php ./cli-script.php dev/build "flush=1&$(QUERYSTRING)"
	php ./cli-script.php dev/tests/all "flush=1&$(QUERYSTRING)"

windmill:
	functest ../cms/tests/test_windmill url=${URL}dev/tests/startsession browser=firefox

jasmine:
	./tests/javascript/server.sh