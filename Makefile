#
# This makefile is a secondary way of installing SilverStripe.
# It is used for things like continuous integration
#
# Most users should simply visit the site root in your web browser.
#
#
URL=`./cli-script.php SapphireInfo/baseurl`

test: phpunit

phpunit:
	php5 ./cli-script.php TestRunner

windmill:
	functest ../cms/tests/test_windmill url=${URL}admin browser=firefox
