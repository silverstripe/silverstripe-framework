title: Extensions
summary: Extensions and DataExtensions let you modify and augment objects transparently. 

# Extensions and DataExtensions

An [Extension](api:SilverStripe\Core\Extension) allows for adding additional functionality to a [Object](api:Object) or modifying existing functionality 
without the hassle of creating a subclass. Developers can add Extensions to any [Object](api:Object) subclass within core, modules
or even their own code to make it more reusable.

Extensions are defined as subclasses of either [DataExtension](api:SilverStripe\ORM\DataExtension) for extending a [DataObject](api:SilverStripe\ORM\DataObject) subclass or 
the [Extension](api:SilverStripe\Core\Extension) class for non DataObject subclasses (such as [Controller](api:SilverStripe\Control\Controller))

<div class="info" markdown="1">
For performance reasons a few classes are excluded from receiving extensions, including `Object`, `ViewableData` 
and `RequestHandler`. You can still apply extensions to descendants of these classes.
</div>

**app/code/extensions/MyMemberExtension.php**


```php
use SilverStripe\ORM\DataExtension;

class MyMemberExtension extends DataExtension 
{

    private static $db = [
        'DateOfBirth' => 'SS_Datetime'
    ];

    public function SayHi() 
    {
        // $this->owner refers to the original instance. In this case a `Member`.
        return "Hi " . $this->owner->Name;
    }
}
```

<div class="info" markdown="1">
Convention is for extension class names to end in `Extension`. This isn't a requirement but makes it clearer
</div>

After this class has been created, it does not yet apply it to any object. We need to tell SilverStripe what classes 
we want to add the `MyMemberExtension` too. To activate this extension, add the following via the [Configuration API](../configuration).

**app/_config/app.yml**


```yml
SilverStripe\Security\Member:
  extensions:
    - MyMemberExtension
```

Alternatively, we can add extensions through PHP code (in the `_config.php` file).


```php
SilverStripe\Security\Member::add_extension('MyMemberExtension');
```

This class now defines a `MyMemberExtension` that applies to all `Member` instances on the website. It will have 
transformed the original `Member` class in two ways:

* Added a new [DBDatetime](api:SilverStripe\ORM\FieldType\DBDatetime) for the users date of birth, and;
* Added a `SayHi` method to output `Hi <User>`

From within the extension we can add more functions, database fields, relations or other properties and have them added
to the underlying `DataObject` just as if they were added to the original `Member` class but without the need to edit
that file directly.


### Adding Database Fields

Extra database fields can be added with a extension in the same manner as if they were placed on the `DataObject` class 
they're applied to. These will be added to the table of the base object - the extension will actually edit the $db, 
$has_one etc.

**app/code/extensions/MyMemberExtension.php**


```php
use SilverStripe\ORM\DataExtension;

class MyMemberExtension extends DataExtension 
{

    private static $db = [
        'Position' => 'Varchar',
    ];

    private static $has_one = [
        'Image' => Image::class,
    ];

    public function SayHi() 
    {
        // $this->owner refers to the original instance. In this case a `Member`.
        return "Hi " . $this->owner->Name;
    }
}
```

**app/templates/Page.ss**


```ss
$CurrentMember.Position
$CurrentMember.Image
```

## Adding Methods

Methods that have a unique name will be called as part of the `__call` method on [Object](api:Object). In the previous example
we added a `SayHi` method which is unique to our extension.

**app/templates/Page.ss**

```ss
<p>$CurrentMember.SayHi</p>
// "Hi Sam"
```

**app/code/Page.php**

```php
use SilverStripe\Security\Security;

$member = Security::getCurrentUser();
echo $member->SayHi;

// "Hi Sam"
```

## Modifying Existing Methods

If the `Extension` needs to modify an existing method it's a little trickier. It requires that the method you want to
customise has provided an *Extension Hook* in the place where you want to modify the data. An *Extension Hook* is done 
through the `extend()` method of the [Extensible](api:SilverStripe\Core\Extensible) trait.

**Member.php**


```php
public function getValidator() 
{
    // ..
    
    $this->extend('updateValidator', $validator);

    // ..
}
```

Extension Hooks can be located anywhere in the method and provide a point for any `Extension` instances to modify the 
variables at that given point. In this case, the core function `getValidator` on the `Member` class provides an 
`updateValidator` hook for developers to modify the core method. The `MyMemberExtension` would modify the core member's
validator by defining the `updateValidator` method.

**app/code/extensions/MyMemberExtension.php**


```php
use SilverStripe\ORM\DataExtension;

class MyMemberExtension extends DataExtension 
{
    public function updateValidator($validator) 
    {
        // we want to make date of birth required for each member
        $validator->addRequiredField('DateOfBirth');
    }
}
```

<div class="info" markdown="1">
The `$validator` parameter is passed by reference, as it is an object.
</div>

Another common example of when you will want to modify a method is to update the default CMS fields for an object in an 
extension. The `CMS` provides a `updateCMSFields` Extension Hook to tie into.


```php
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\ORM\DataExtension;

class MyMemberExtension extends DataExtension 
{

    private static $db = [
        'Position' => 'Varchar',
    ];

    private static $has_one = [
        'Image' => 'Image',
    ];

    public function updateCMSFields(FieldList $fields) 
    {
        $fields->push(new TextField('Position'));
        $fields->push($upload = new UploadField('Image', 'Profile Image'));
        $upload->setAllowedFileCategories('image/supported');
    }
}
```

<div class="notice" markdown="1">
If you're providing a module or working on code that may need to be extended by  other code, it should provide a *hook* 
which allows an Extension to modify the results. 
</div>


```php
public function Foo() 
{
    $foo = // ..

    $this->extend('updateFoo', $foo);

    return $foo;
}
```

The convention for extension hooks is to provide an `update{$Function}` hook at the end before you return the result. If
you need to provide extension hooks at the beginning of the method use `before{..}`.

## Owner

In your [Extension](api:SilverStripe\Core\Extension) class you can only refer to the source object through the `owner` property on the class as 
`$this` will refer to your `Extension` instance.


```php
use SilverStripe\ORM\DataExtension;

class MyMemberExtension extends DataExtension 
{
    public function updateFoo($foo) 
    {
        // outputs the original class
        var_dump($this->owner);
    }
}
```

## Checking to see if an Object has an Extension

To see what extensions are currently enabled on an object, use the [getExtensionInstances()](api:SilverStripe\Core\Extensible::getExtensionInstances()) and 
[hasExtension()](api:SilverStripe\Core\Extensible::hasExtension()) methods of the [Extensible](api:SilverStripe\Core\Extensible) trait.

```php
$member = Security::getCurrentUser();

print_r($member->getExtensionInstances());

if($member->hasExtension('MyCustomMemberExtension')) {
    // ..
}
```

## Extension injection points

`Extensible` has two additional methods, `beforeExtending` and `afterExtending`, each of which takes a method name and a 
callback to be executed immediately before and after `extend()` is called on extensions.

This is useful in many cases where working with modules such as `Translatable` which operate on `DataObject` fields 
that must exist in the `FieldList` at the time that `$this->extend('UpdateCMSFields')` is called.

<div class="notice" markdown='1'>
Please note that each callback is only ever called once, and then cleared, so multiple extensions to the same function 
require that a callback is registered each time, if necessary.
</div>

Example: A class that wants to control default values during object  initialization. The code needs to assign a value 
if not specified in `self::$defaults`, but before extensions have been called:


```php
public function __construct() 
{
    $this->beforeExtending('populateDefaults', function() {
        if(empty($this->MyField)) {
            $this->MyField = 'Value we want as a default if not specified in $defaults, but set before extensions';
        }
    });

    parent::__construct();
}
```

Example 2: User code can intervene in the process of extending cms fields.

<div class="notice" markdown="1">
This method is preferred to disabling, enabling, and calling field extensions manually.
</div>


```php
public function getCMSFields() 
{
    $this->beforeUpdateCMSFields(function($fields) {
        // Include field which must be present when updateCMSFields is called on extensions
        $fields->addFieldToTab("Root.Main", new TextField('Detail', 'Details', null, 255));
    });

    $fields = parent::getCMSFields();
    // ... additional fields here
    return $fields;
}
```

## Related Lessons
* [DataExtensions and SiteConfig](https://www.silverstripe.org/learn/lessons/v4/data-extensions-and-siteconfig-1)


## Related Documentaion

* [Injector](injector/)

## API Documentation

* [Extension](api:SilverStripe\Core\Extension)
* [DataExtension](api:SilverStripe\ORM\DataExtension)
