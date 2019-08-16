---
title: How to use a FixtureFactory
---

# How to use a FixtureFactory

The [FixtureFactory](api:SilverStripe\Dev\FixtureFactory) is used to manually create data structures for use with tests. For more information on fixtures
see the [Fixtures](../fixtures) documentation.

In this how to we'll use a `FixtureFactory` and a custom blue print for giving us a shortcut for creating new objects
with information that we need.


```php
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Injector\Injector;

class MyObjectTest extends SapphireTest 
{

    protected $factory;

    function __construct() {
        parent::__construct();

        $factory = Injector::inst()->create('FixtureFactory');

        // Defines a "blueprint" for new objects
        $factory->define('MyObject', [
            'MyProperty' => 'My Default Value'
        ]);

        $this->factory = $factory;
    }

    function testSomething() {
        $MyObjectObj = $this->factory->createObject(
            'MyObject',
            ['MyOtherProperty' => 'My Custom Value']
        );

        echo $MyObjectObj->MyProperty;
        // returns "My Default Value"

        echo $myPageObj->MyOtherProperty;
        // returns "My Custom Value"
    }
}
```

## Related Documentation

* [Fixtures](../fixtures)

## API Documentation

* [FixtureFactory](api:SilverStripe\Dev\FixtureFactory)
* [FixtureBlueprint](api:SilverStripe\Dev\FixtureBlueprint)
