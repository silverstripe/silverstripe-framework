title: Aspects
summary: Introduction to using aspect-oriented programming with SilverStripe.

# Aspects

Aspect oriented programming is the idea that some logic abstractions can be applied across various type hierarchies 
"after the fact", altering the behavior of the system without altering the code structures that are already in place. 

> In computing, aspect-oriented programming (AOP) is a programming paradigm which isolates secondary or supporting 
> functions from the main program's business logic. It aims to increase modularity by allowing the separation of 
> cross-cutting concerns, forming a basis for aspect-oriented software development.

<div class="notice" markdown="1">
[Wikipedia](http://en.wikipedia.org/wiki/Aspect-oriented_programming) provides a much more in-depth explanation.
</div>

In the context of the SilverStripe [Dependency Injector](injector), Aspects are achieved thanks to PHP's `__call` magic 
method combined with the `Proxy` Design Pattern.

Assume an existing service declaration exists called `MyService`. An `AopProxyService` class instance is created, and 
the existing `MyService` object is bound in as a member variable of the `AopProxyService` class.

Objects are added to the `AopProxyService` instance's "beforeCall" and "afterCall" lists; each of these implements 
either the beforeCall or afterCall method.

When client code declares a `$dependency` on MyService, it is actually passed in the `AopProxyService` instance.

Client code calls a method `MyMethod` that it knows exists on `MyService` - this doesn't exist on `AopProxyService`, so 
__call is triggered.

All classes bound to the `beforeCall` list are executed; if any explicitly returns 'false', `myMethod` is not executed.
Otherwise, `myMethod` is executed. 

All classes bound to the `afterCall` list are executed.

To provide some context, imagine a situation where we want to direct all `write` queries made in the system to a 
specific database server, whereas all read queries can be handled by slave servers. 

A simplified implementation might look like the following.

<div class="notice" markdown="1">
This doesn't cover all cases used by SilverStripe so is not a complete solution, more just a guide to how it would be 
used. 
</div>

**mysite/code/MySQLWriteDbAspect.php**

	:::php
	<?php

	class MySQLWriteDbAspect implements BeforeCallAspect {

		/**
		 * @var MySQLDatabase
		 */
		public $writeDb;
		
		public $writeQueries = array(
			'insert','update','delete','replace'
		);


		public function beforeCall($proxied, $method, $args, &$alternateReturn) {
			if (isset($args[0])) {
				$sql = $args[0];
				$code = isset($args[1]) ? $args[1] : E_USER_ERROR;

				if (in_array(strtolower(substr($sql,0,strpos($sql,' '))), $this->writeQueries)) {
					$alternateReturn = $this->writeDb->query($sql, $code);
					return false;
				}
			}
		}
	}

To actually make use of this class, a few different objects need to be configured. First up, define the `writeDb`
object that's made use of above.

**mysite/_config/app.yml**

	:::yml
	WriteMySQLDatabase:
	  class: MySQLDatabase
	  constructor:
	    - type: MySQLDatabase
	      server: write.hostname.db
	      username: user
	      password: pass
	      database: write_database

This means that whenever something asks the [api:Injector] for the `WriteMySQLDatabase` object, it'll receive an object 
of type `MySQLDatabase`, configured to point at the 'write_database'.

Next, this should be bound into an instance of the `Aspect` class

**mysite/_config/app.yml**

	:::yml
	MySQLWriteDbAspect:
	  properties:
	    writeDb: %$WriteMySQLDatabase


Next, we need to define the database connection that will be used for all non-write queries

**mysite/_config/app.yml**
	
	:::yml
	ReadMySQLDatabase:
	  class: MySQLDatabase
	  constructor:
	    - type: MySQLDatabase
	    server: slavecluster.hostname.db
	    username: user
	    password: pass
	    database: read_database

The final piece that ties everything together is the [api:AopProxyService] instance that will be used as the replacement
object when the framework creates the database connection.

**mysite/_config/app.yml**
	
	:::yml
	MySQLDatabase:
	  class: AopProxyService
	  properties:
	    proxied: %$ReadMySQLDatabase
	    beforeCall:
	      query: 
	        - %$MySQLWriteDbAspect

The two important parts here are in the `properties` declared for the object.

- **proxied** : This is the 'read' database connection that all queries should be initially directed through.
- **beforeCall** : A hash of method\_name => array containing objects that are to be evaluated _before_ a call to the 
defined method\_name

Overall configuration for this would look as follows

**mysite/_config/app.yml**
	
	:::yml
	Injector:
	  ReadMySQLDatabase:
	    class: MySQLDatabase
	    constructor:
	      - type: MySQLDatabase
	        server: slavecluster.hostname.db
	        username: user
	        password: pass
	        database: read_database
	  MySQLWriteDbAspect:
	    properties:
	      writeDb: %$WriteMySQLDatabase
	  WriteMySQLDatabase:
	    class: MySQLDatabase
	    constructor:
	      - type: MySQLDatabase
	        server: write.hostname.db
	        username: user
	        password: pass
	        database: write_database
	  MySQLDatabase:
	    class: AopProxyService
	    properties:
	      proxied: %$ReadMySQLDatabase
	      beforeCall:
	        query: 
	          - %$MySQLWriteDbAspect


## Changing what a method returns

One major feature of an `Aspect` is the ability to modify what is returned from the client's call to the proxied method.
As seen in the above example, the `beforeCall` method modifies the `&$alternateReturn` variable, and returns `false` 
after doing so. 
	
	:::php
	$alternateReturn = $this->writeDb->query($sql, $code);

	return false;

By returning `false` from the `beforeCall()` method, the wrapping proxy class will_not_ call any additional `beforeCall`
handlers defined for the called method. Assigning the `$alternateReturn` variable also indicates to return that value
to the caller of the method. 

Similarly the `afterCall()` aspect can be used to manipulate the value to be returned to the calling code. All the
`afterCall()` method needs to do is return a non-null value, and that value will be returned to the original calling 
code instead of the actual return value of the called method. 


## API Documentation

* [api:AopProxyService]
* [api:BeforeCallAspect]
* [api:AfterCallAspect]
* [api:Injector]
