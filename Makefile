#
# This makefile is a secondary way of installing SilverStripe.
# It is used for things like continuous integration
#
# Most users should simply visit the site root in your web browser.
#
#
URL=`php5 ./cli-script.php SapphireInfo/baseurl`

test: phpunit

phpunit:
	php5 ./cli-script.php dev/build flush=1
	php5 ./cli-script.php dev/tests/all flush=1

windmill:
	functest ../cms/tests/test_windmill url=${URL}dev/tests/startsession browser=firefox
