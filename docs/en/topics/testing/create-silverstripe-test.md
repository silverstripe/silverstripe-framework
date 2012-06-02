#  How To Create a SilverStripe Test

A unit test class will test the behaviour of one of your `[api:DataObjects]`.  This simple fragment of `[api:SiteTreeTest]`
provides us the basics of creating unit tests.

	:::php
	<?php
	class SiteTreeTest extends SapphireTest {
		
		// Define the fixture file to use for this test class
		static $fixture_file = 'SiteTreeTest.yml';
	
		/**	
		 * Test generation of the URLSegment values.
		 *  - Turns things into lowercase-hyphen-format
		 *  - Generates from Title by default, unless URLSegment is explicitly set
		 *  - Resolves duplicates by appending a number
		 */
		public function testURLGeneration() {
			$expectedURLs = array(
				'home' => 'home',
				'staff' => 'my-staff',
				'about' => 'about-us',
				'staffduplicate' => 'my-staff-2',
				'product1' => '1-1-test-product',
				'product2' => 'another-product',
				'product3' => 'another-product-2',
				'product4' => 'another-product-3',
			);
			
			foreach($expectedURLs as $fixture => $urlSegment) {
				$obj = $this->objFromFixture('Page', $fixture);
				$this->assertEquals($urlSegment, $obj->URLSegment);
			}
		}
	}
	


There are a number of points to note in this code fragment:

*  Your test is a **subclass of SapphireTest**.  Both unit tests and functional tests are a subclass of `[api:SapphireTest]`.
*  **static $fixture_file** is defined.  The testing framework will automatically set up a new database for **each** of
your tests.  The initial database content will be sourced from the YML file that you list in $fixture_file. The property can take an array of fixture paths.
*  Each **method that starts with the word "test"** will be executed by the TestRunner.  Define as many as you like; the
database will be rebuilt for each of these.
*  **$this->objFromFixture($className, $identifier)** can be used to select one of the objects named in your fixture
file.  To identify to the object, we provide a class name and an identifier.  The identifier is specified in the YML
file but not saved in the database anywhere.  objFromFixture() looks the `[api:DataObject]` up in memory rather than using the
database.  This means that you can use it to test the functions responsible for looking up content in the database.

## Assertion commands

**$this->assertEquals()** is an example of an assertion function.  
These functions form the basis of our tests - a test
fails if and only if one or more of the assertions fail.  
See [the PHPUnit manual](http://www.phpunit.de/manual/current/en/api.html#api.assert)
for a listing of all PHPUnit's built-in assertions.

The `[api:SapphireTest]` class comes with additional assertions which are more
specific to the framework, e.g. `[assertEmailSent](api:SapphireTest->assertEmailSent())`
which can simulate sending emails through the `Email->send()` API without actually
using a mail server (see the [testing emails](email-sending)) guide.

## The Database YAML file

The main feature of `[api:SapphireTest]` over the raw PHPUnit classes is that SapphireTest will prepare a temporary database for
you.  The content of that database is provided in a special YAML file.  YAML is a simple markup languages that uses tabs
and colons instead of the more verbose XML tags, and because of this much better for developers creating files by hand.

We will begin with a sample file and talk our way through it.

	Page:
	    home:
	        Title: Home
	    about:
	        Title: About Us
	    staff:
	        Title: Staff
	        URLSegment: my-staff
	        Parent: =>Page.about
	    staffduplicate:
	        Title: Staff
	        URLSegment: my-staff
	        Parent: =>Page.about
	    products:
	        Title: Products
	    product1:
	        Title: 1.1 Test Product
	    product2:
	        Title: Another Product
	    product3:
	        Title: Another Product
	    product4:
	        Title: Another Product
	    contact:
	        Title: Contact Us
	        
	ErrorPage:
	    404:
	        Title: Page not Found
	        ErrorCode: 404


The contents of the YAML file are broken into three levels.

*  **Top level: class names** - Page and ErrorPage.  This is the name of the dataobject class that should be created. 
The fact that ErrorPage is actually a subclass is irrelevant to the system populating the database.  It just
instantiates the object you specify.
*  **Second level: identifiers** - home, about, staff, staffduplicate, etc.  These are the identifiers that you pass as
the second argument of SapphireTest::objFromFixture().  Each identifier you specify delimits a new database record. 
This means that every record needs to have an identifier, whether you use it or not.
*  **Third level: fields** - each field for the record is listed as a 3rd level entry.  In most cases, the field's raw
content is provided.  However, if you want to define a relationship, you can do so using "=>".

There are a couple of lines like this:

	Parent: =>Page.about

This will tell the system to set the ParentID database field to the ID of the Page object with the identifier "about". 
This can be used on any has-one or many-many relationship.  Note that we use the name of the relationship (Parent), and
not the name of the database field (ParentID)

On many-many relationships, you should specify a comma separated list of values.

	MyRelation: =>Class.inst1,=>Class.inst2,=>Class.inst3

An crucial thing to note is that **the YAML file specifies DataObjects, not database records**.  The database is
populated by instantiating DataObject objects, setting the fields listed, and calling write().  This means that any
onBeforeWrite() or default value logic will be executed as part of the test.  This forms the basis of our
testURLGeneration() test above.

For example, the URLSegment value of Page.staffduplicate is the same as the URLSegment value of Page.staff.  When the
fixture is set up, the URLSegment value of Page.staffduplicate will actually be my-staff-2.

Finally, be aware that requireDefaultRecords() is **not** called by the database populator - so you will need to specify
standard pages such as 404 and home in your YAML file.
