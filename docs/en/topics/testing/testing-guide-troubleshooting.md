# Troubleshooting

Part of the [SilverStripe Testing Guide](testing-guide).

## I can't run my new test class

If you've just added a test class, but you can't see it via the web interface, chances are, you haven't flushed your
manifest cache - append `?flush=1` to the end of your URL querystring.

## Class 'PHPUnit_Framework_MockObject_Generator' not found

This is due to an upgrade in PHPUnit 3.5 which PEAR doesn't handle correctly.<br>
It can be fixed by running the following commands:

	pear install -f phpunit/DbUnit
	pear install -f phpunit/PHPUnit_MockObject
	pear install -f phpunit/PHPUnit_Selenium
