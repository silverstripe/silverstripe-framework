---
title: Relations between Records
summary: Relate models together using the ORM using has_one, has_many, and many_many.
icon: link
---

# Relations between Records

In most situations you will likely see more than one [DataObject](api:SilverStripe\ORM\DataObject) and several classes in your data model may relate
to one another. An example of this is a `Player` object may have a relationship to one or more `Team` or `Coach` classes
and could take part in many `Games`. Relations are a key part of designing and building a good data model.

Relations are built through static array definitions on a class, in the format `<relationship-name> => <classname>`.
SilverStripe supports a number of relationship types and each relationship type can have any number of relations.

## has_one

Many-to-1 and 1-to-1 relationships create a database-column called "`<relationship-name>`ID", in the example below this would be "TeamID" on the "Player"-table.

```php
use SilverStripe\ORM\DataObject;

class Player extends DataObject
{
    private static $has_one = [
        "Team" => Team::class,
    ];
}

class Team extends DataObject
{
    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_many = [
        'Players' => Player::class,
    ];
}
```

This defines a relationship called `Team` which links to a `Team` class. The `ORM` handles navigating the relationship
and provides a short syntax for accessing the related object.

To create a has_one/has_many relationship to core classes (File, Image, etc), reference the Classname::class, like below.

```php
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\File;

class Team extends DataObject
{
    private static $has_many = [
        'Teamphoto' => Image::class,
        'Lineup' => File::class
    ];    
}
```

At the database level, the `has_one` creates a `TeamID` field on `Player`. A `has_many` field does not impose any database changes. It merely injects a new method into the class to access the related records (in this case, `Players()`)

```php
$player = Player::get()->byId(1);

$team = $player->Team();
// returns a 'Team' instance.

echo $player->Team()->Title;
// returns the 'Title' column on the 'Team' or `getTitle` if it exists.
```

The relationship can also be navigated in [templates](../templates).

```ss
<% with $Player %>
    <% if $Team %>
        Plays for $Team.Title
    <% end_if %>
<% end_with %>
```

## Polymorphic has_one

A has_one can also be polymorphic, which allows any type of object to be associated.
This is useful where there could be many use cases for a particular data structure.

An additional column is created called "`<relationship-name>`Class", which along
with the ID column identifies the object.

To specify that a has_one relation is polymorphic set the type to [api:SilverStripe\ORM\DataObject]
Ideally, the associated has_many (or belongs_to) should be specified with dot notation.

```php
use SilverStripe\ORM\DataObject;

class Player extends DataObject
{
    private static $has_many = [
        "Fans" => Fan::class.".FanOf",
    ];
}
class Team extends DataObject
{
    private static $has_many = [
        "Fans" => Fan::class.".FanOf",
    ];
}

// Type of object returned by $fan->FanOf() will vary
class Fan extends DataObject
{

    // Generates columns FanOfID and FanOfClass
    private static $has_one = [
        "FanOf" => DataObject::class,
    ];
}
```

[warning]
Note: The use of polymorphic relationships can affect query performance, especially
on joins, and also increases the complexity of the database and necessary user code.
They should be used sparingly, and only where additional complexity would otherwise
be necessary. E.g. Additional parent classes for each respective relationship, or
duplication of code.
[/warning]

## has_many

Defines 1-to-many joins. As you can see from the previous example, `$has_many` goes hand in hand with `$has_one`.

[alert]
Please specify a $has_one-relationship on the related child-class as well, in order to have the necessary accessors
available on both ends. To add a $has_one-relationship on core classes, yml config settings can be used:
```yml
SilverStripe\Assets\Image:
  has_one:
    MyDataObject: MyDataObject
```
[/alert]

```php
use SilverStripe\ORM\DataObject;

class Team extends DataObject
{
    private static $db = [
        'Title' => 'Varchar',
    ];

    private static $has_many = [
        'Players' => Player::class,
    ];
}
class Player extends DataObject
{

    private static $has_one = [
        "Team" => Team::class,
    ];
}
```

Much like the `has_one` relationship, `has_many` can be navigated through the `ORM` as well. The only difference being
you will get an instance of [HasManyList](api:SilverStripe\ORM\HasManyList) rather than the object.

```php
$team = Team::get()->first();

echo $team->Players();
// [HasManyList]

echo $team->Players()->Count();
// returns '14';

foreach($team->Players() as $player) {
    echo $player->FirstName;
}
```

To specify multiple `$has_many` to the same object you can use dot notation to distinguish them like below:

```php
use SilverStripe\ORM\DataObject;

class Person extends DataObject
{
    private static $has_many = [
        "Managing" => Company::class.".Manager",
        "Cleaning" => Company::class.".Cleaner",
    ];
}
class Company extends DataObject
{
    private static $has_one = [
        "Manager" => Person::class,
        "Cleaner" => Person::class,
    ];
}
```

Multiple `$has_one` relationships are okay if they aren't linking to the same object type. Otherwise, they have to be
named.

If you're using the default scaffolded form fields with multiple `has_one` relationships, you will end up with a CMS field for each relation. If you don't want these you can remove them by their IDs:

```php
public function getCMSFields()
{
    $fields = parent::getCMSFields();
    $fields->removeByName(array('ManagerID', 'CleanerID'));
    return $fields;
}
```

## belongs_to

Defines a 1-to-1 relationship with another object, which declares the other end of the relationship with a
corresponding `$has_one`. A single database column named `<relationship-name>ID` will be created in the object with the
`$has_one`, but the $belongs_to by itself will not create a database field. This field will hold the ID of the object
declaring the `$belongs_to`.

Similarly with `$has_many`, dot notation can be used to explicitly specify the `$has_one` which refers to this relation.
This is not mandatory unless the relationship would be otherwise ambiguous.

```php
use SilverStripe\ORM\DataObject;

class Team extends DataObject
{

    private static $has_one = [
        'Coach' => Coach::class
    ];
}
class Coach extends DataObject
{

    private static $belongs_to = [
        'Team' => Team::class.'.Coach'
    ];
}
```

## many_many

Defines many-to-many joins, which uses a third table created between the two to join pairs.
There are two ways in which this can be declared, which are described below, depending on
how the developer wishes to manage this join table.

[warning]
Please specify a $belongs_many_many-relationship on the related class as well, in order
to have the necessary accessors available on both ends.
[/warning]

Much like the `has_one` relationship, `many_many` can be navigated through the `ORM` as well.
The only difference being you will get an instance of [ManyManyList](api:SilverStripe\ORM\ManyManyList) or
[ManyManyThroughList](api:SilverStripe\ORM\ManyManyThroughList) rather than the object.

```php
$team = Team::get()->byId(1);

$supporters = $team->Supporters();
// returns a 'ManyManyList' instance.
```

### Automatic many_many table

If you specify only a single class as the other side of the many-many relationship, then a
table will be automatically created between the two (this-class)_(relationship-name), will
be created with a pair of ID fields.

Extra fields on the mapping table can be created by declaring a `many_many_extraFields`
config to add extra columns.

```php
use SilverStripe\ORM\DataObject;

class Team extends DataObject
{
    private static $many_many = [
        "Supporters" => Supporter::class,
    ];

    private static $many_many_extraFields = [
        'Supporters' => [
          'Ranking' => 'Int'
        ]
    ];
}

class Supporter extends DataObject
{
    private static $belongs_many_many = [
        "Supports" => Team::class,
    ];
}
```

To ensure this `many_many` is sorted by "Ranking" by default you can add this to your config:

```yaml
Team_Supporters:
  default_sort: '"Team_Supporter"."Ranking" ASC'
```

`Team_Supporters` is the table name automatically generated for the many_many relation in this case.

### many_many through relationship joined on a separate DataObject

If necessary, a third DataObject class can instead be specified as the joining table,
rather than having the ORM generate an automatically scaffolded table. This has the following
advantages:

 - Allows versioning of the mapping table, including support for the
   [ownership api](/developer_guides/model/versioning).
 - Allows support of other extensions on the mapping table (e.g. subsites).
 - Extra fields can be managed separately to the joined dataobject, even via a separate
   GridField or form.

This is declared via array syntax, with the following keys on the many_many:
 - `through` Class name of the mapping table
 - `from` Name of the has_one relationship pointing back at the object declaring many_many
 - `to` Name of the has_one relationship pointing to the object declaring belongs_many_many.

Just like a any normal DataObject, you can apply a default sort which will be applied when
accessing many many through relations.

Note: The `through` class must not also be the name of any field or relation on the parent
or child record.

The syntax for `belongs_many_many` is unchanged.

```php
use SilverStripe\ORM\DataObject;

class Team extends DataObject
{
    private static $many_many = [
        "Supporters" => [
            'through' => TeamSupporter::class,
            'from' => 'Team',
            'to' => 'Supporter',
        ]
    ];
}
class Supporter extends DataObject
{
    // Prior to 4.2.0, this also needs to include the reverse relation name via dot-notation
    // i.e. 'Supports' => Team::class . '.Supporters'
    private static $belongs_many_many = [
        'Supports' => Team::class,
    ];
}
class TeamSupporter extends DataObject
{
    private static $db = [
        'Ranking' => 'Int',
    ];

    private static $has_one = [
        'Team' => Team::class,
        'Supporter' => Supporter::class,
    ];

    private static $default_sort = '"TeamSupporter"."Ranking" ASC'
}
```

In order to filter on the join table during queries, you can use the class name of the joining table
for any sql conditions.

```php
$team = Team::get()->byId(1);
$supporters = $team->Supporters()->where(['"TeamSupporter"."Ranking"' => 1]);
```

Note: ->filter() currently does not support joined fields natively due to the fact that the
query for the join table is isolated from the outer query controlled by DataList.

### Polymorphic many_many (Experimental)

Using many_many through, it is possible to support polymorphic relations on the mapping table.
Note, that this feature is currently experimental, and has certain limitations:
 - This feature only works with many_many through
 - This feature will only allow polymorphic many_many, but not belongs_many_many. However,
   you can have a has_many relation to the mapping table on this side, and iterate through this
   to collate parent records.

For instance, this is how you would link an arbitrary object to many_many tags.

```php
use SilverStripe\ORM\DataObject;

class SomeObject extends DataObject
{
    // This same many_many may also exist on other classes
    private static $many_many = [
        "Tags" => [
            'through' => TagMapping::class,
            'from' => 'Parent',
            'to' => 'Tag',
        ]
    ];
}
class Tag extends DataObject
{
    // has_many works, but belongs_many_many will not
    private static $has_many = [
        'TagMappings' => TagMapping::class,
    ];

    /**
     * Example iterator placeholder for belongs_many_many.
     * This is a list of arbitrary types of objects
     * @return Generator|DataObject[]
     */
    public function TaggedObjects()
    {
        foreach ($this->TagMappings() as $mapping) {
            yield $mapping->Parent();
        }
    }

}
class TagMapping extends DataObject
{   
    private static $has_one = [
        'Parent' => DataObject::class, // Polymorphic has_one
        'Tag' => Tag::class,
    ];
}
```

### Using many_many in templates

The relationship can also be navigated in [templates](../templates).
The joined record can be accessed via `Join` or `TeamSupporter` property (many_many through only)

```ss
<% with $Supporter %>
    <% loop $Supports %>
        Supports $Title <% if $TeamSupporter %>(rank $TeamSupporter.Ranking)<% end_if %>
    <% end_if %>
<% end_with %>
```

You can also use `$Join` in place of the join class alias (`$TeamSupporter`), if your template
is class-agnostic and doesn't know the type of the join table.

## belongs_many_many

The belongs_many_many represents the other side of the relationship on the target data class.
When using either a basic many_many or a many_many through, the syntax for belongs_many_many is the same.

To specify multiple $many_manys between the same classes, specify use the dot notation to
distinguish them like below:


```php
use SilverStripe\ORM\DataObject;

class Category extends DataObject
{

    private static $many_many = [
        'Products' => Product::class,
        'FeaturedProducts' => Product::class,
    ];
}

class Product extends DataObject
{   
    private static $belongs_many_many = [
        'Categories' => Category::class.'.Products',
        'FeaturedInCategories' => Category::class.'.FeaturedProducts',
    ];
}
```

If you're unsure about whether an object should take on `many_many` or `belongs_many_many`,
the best way to think about it is that the object where the relationship will be edited
(i.e. via checkboxes) should contain the `many_many`. For instance, in a `many_many` of
Product => Categories, the `Product` should contain the `many_many`, because it is much
more likely that the user will select Categories for a Product than vice-versa.


## Cascading deletions

Relationships between objects can cause cascading deletions, if necessary, through configuration of the
`cascade_deletes` config on the parent class.

```php
use SilverStripe\ORM\DataObject;

class ParentObject extends DataObject {
    private static $has_one = [
        'Child' => ChildObject::class,
    ];
    private static $cascade_deletes = [
        'Child',
    ];
}
class ChildObject extends DataObject {
}
```

In this example, when the Parent object is deleted, the Child specified by the has_one relation will also
be deleted. Note that all relation types (has_many, many_many, belongs_many_many, belongs_to, and has_one)
are supported, as are methods that return lists of objects but do not correspond to a physical database relation.

If your object is versioned, cascade_deletes will also act as "cascade unpublish", such that any unpublish
on a parent object will trigger unpublish on the child, similarly to how `owns` causes triggered publishing.
See the [versioning docs](/developer_guides/model/versioning) for more information on ownership.

[alert]
Declaring cascade_deletes implies delete permissions on the listed objects.
Built-in controllers using delete operations check canDelete() on the owner, but not on the owned object.   
[/alert]

## Cascading duplications

Similar to `cascade_deletes` there is also a `cascade_duplicates` config which works in much the same way.
When you invoke `$dataObject->duplicate()`, relation names specified by this config will be duplicated
and saved against the new clone object.

Note that duplications will act differently depending on the kind of relation:
 - Exclusive relationships (e.g. has_many, belongs_to) will be explicitly duplicated.
 - Non-exclusive many_many will not be duplicated, but the mapping table values will instead
   be copied for this record.
 - Non-exclusive has_one relationships are not normally necessary to duplicate, since both parent and clone
   can normally share the same relation ID. However, if this is declared in `cascade_duplicates` any
   has one will be similarly duplicated as though it were an exclusive relationship.

For example:

```php
use SilverStripe\ORM\DataObject;

class ParentObject extends DataObject {
    private static $many_many = [
        'RelatedChildren' => ChildObject::class,
    ];
    private static $cascade_duplicates = [ 'RelatedChildren' ];
}
class ChildObject extends DataObject {
}
```

When duplicating objects you can disable recursive duplication by passing in `false` to the second
argument of duplicate().

E.g.

```php
$parent = ParentObject::get()->first();
$dupe = $parent->duplicate(true, false);
```

## Adding relations

Adding new items to a relations works the same, regardless if you're editing a **has_many** or a **many_many**. They are
encapsulated by [HasManyList](api:SilverStripe\ORM\HasManyList) and [ManyManyList](api:SilverStripe\ORM\ManyManyList), both of which provide very similar APIs, e.g. an `add()`
and `remove()` method.

```php
$team = Team::get()->byId(1);

// create a new supporter
$supporter = new Supporter();
$supporter->Name = "Foo";
$supporter->write();

// add the supporter.
$team->Supporters()->add($supporter);
```

## Custom Relations

You can use the ORM to get a filtered result list without writing any SQL. For example, this snippet gets you the
"Players"-relation on a team, but only containing active players.

See [DataObject::$has_many](api:SilverStripe\ORM\DataObject::$has_many) for more info on the described relations.

```php
use SilverStripe\ORM\DataObject;

class Team extends DataObject
{
    private static $has_many = [
        "Players" => Player::class
    ];

    public function ActivePlayers()
    {
        return $this->Players()->filter('Status', 'Active');
    }
}

```

[notice]
Adding new records to a filtered `RelationList` like in the example above doesn't automatically set the filtered
criteria on the added record.
[/notice]

## Relations on Unsaved Objects

You can also set *has_many* and *many_many* relations before the `DataObject` is saved. This behavior uses the
[UnsavedRelationList](api:SilverStripe\ORM\UnsavedRelationList) and converts it into the correct `RelationList` when saving the `DataObject` for the first
time.

This unsaved lists will also recursively save any unsaved objects that they contain.

As these lists are not backed by the database, most of the filtering methods on `DataList` cannot be used on a list of
this type. As such, an `UnsavedRelationList` should only be used for setting a relation before saving an object, not
for displaying the objects contained in the relation.

## Link Tracking

You can control the visibility of the `Link Tracking` tab by setting the `show_sitetree_link_tracking` config.
This defaults to `false` for most `DataObject`'s.

It is also possible to control the visibility of the `File Tracking` tab by setting the `show_file_link_tracking` config.

## Related Lessons
* [Working with data relationships -- has_many](https://www.silverstripe.org/learn/lessons/v4/working-with-data-relationships-has-many-1)
* [Working with data relationships -- many_many](https://www.silverstripe.org/learn/lessons/v4/working-with-data-relationships-many-many-1)

## Related Documentation

* [Introduction to the Data Model and ORM](data_model_and_orm)
* [Lists](lists)

## API Documentation

* [HasManyList](api:SilverStripe\ORM\HasManyList)
* [ManyManyList](api:SilverStripe\ORM\ManyManyList)
* [DataObject](api:SilverStripe\ORM\DataObject)
* [LinkTracking](api:SilverStripe\CMS\Model\SiteTreeLinkTracking)
