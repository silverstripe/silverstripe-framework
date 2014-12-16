title: Sessions
summary: A set of static methods for manipulating PHP sessions.

# Sessions

Session support in PHP consists of a way to preserve certain data across subsequent accesses such as logged in user
information and security tokens.

In order to support things like testing, the session is associated with a particular Controller.  In normal usage,
this is loaded from and saved to the regular PHP session, but for things like static-page-generation and
unit-testing, you can create multiple Controllers, each with their own session.
 
## set

	:::php
	Session::set('MyValue', 6);

Saves the value of to session data. You can also save arrays or serialized objects in session (but note there may be 
size restrictions as to how much you can save).

	:::php
	// saves an array
	Session::set('MyArrayOfValues', array('1','2','3'));

	// saves an object (you'll have to unserialize it back)
	$object = new Object();
	Session::set('MyObject', serialize($object));
 
## get

Once you have saved a value to the Session you can access it by using the `get` function. Like the `set` function you 
can use this anywhere in your PHP files.

	:::php
	echo Session::get('MyValue'); 
	// returns 6

	$data = Session::get('MyArrayOfValues'); 
	// $data = array(1,2,3)

	$object = unserialize(Session::get('MyObject', $object)); 
	// $object = Object()

## get_all

You can also get all the values in the session at once. This is useful for debugging.
	
	:::php
	Session::get_all(); 
	// returns an array of all the session values.

## clear

Once you have accessed a value from the Session it doesn't automatically wipe the value from the Session, you have
to specifically remove it. 

	:::php
	Session::clear('MyValue');

Or you can clear every single value in the session at once. Note SilverStripe stores some of its own session data
including form and page comment information. None of this is vital but `clear_all` will clear everything.
	
	:::php
	Session::clear_all();

## API Documentation

* [api:Session]