title: Fixtures
summary: Populate test databases with fake seed data.

# Fixtures

To test functionality correctly, we must use consistent data. If we are testing our code with the same data each
time, we can trust our tests to yield reliable results and to identify when the logic changes. Each test run in
SilverStripe starts with a fresh database containing no records. `Fixtures` provide a way to describe the initial data
to load into the database. The [SapphireTest](api:SilverStripe\Dev\SapphireTest) class takes care of populating a test database with data from
fixtures - all we have to do is define them.

To include your fixture file in your tests, you should define it as your `$fixture_file`:


**app/tests/MyNewTest.php**


```php
use SilverStripe\Dev\SapphireTest;

class MyNewTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures.yml';
}
```

You can also use an array of fixture files, if you want to use parts of multiple other tests.

If you are using [api:SilverStripe\Dev\TestOnly] dataobjects in your fixtures, you must
declare these classes within the $extra_dataobjects variable.

**app/tests/MyNewTest.php**

```php
use SilverStripe\Dev\SapphireTest;

class MyNewTest extends SapphireTest
{
    protected static $fixture_file = [
        'fixtures.yml',
        'otherfixtures.yml'
    ];

    protected static $extra_dataobjects = [
        Player::class,
        Team::class,
    ];
}
```

Typically, you'd have a separate fixture file for each class you are testing - although overlap between tests is common.

Fixtures are defined in `YAML`. `YAML` is a markup language which is deliberately simple and easy to read, so it is
ideal for fixture generation. Say we have the following two DataObjects:


```php
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class Player extends DataObject implements TestOnly
{
    private static $db = [
        'Name' => 'Varchar(255)'
    ];

    private static $has_one = [
        'Team' => 'Team'
    ];
}

class Team extends DataObject implements TestOnly
{
    private static $db = [
        'Name' => 'Varchar(255)',
        'Origin' => 'Varchar(255)'
    ];

    private static $has_many = [
        'Players' => 'Player'
    ];
}
```

We can represent multiple instances of them in `YAML` as follows:

**app/tests/fixtures.yml**

```yml

Team:
  hurricanes:
    Name: The Hurricanes
    Origin: Wellington
  crusaders:
    Name: The Crusaders
    Origin: Canterbury
Player:
  john:
    Name: John
    Team: =>Team.hurricanes
  joe:
    Name: Joe
    Team: =>Team.crusaders
  jack:
    Name: Jack
    Team: =>Team.crusaders
```

This `YAML` is broken up into three levels, signified by the indentation of each line. In the first level of
indentation, `Player` and `Team`, represent the class names of the objects we want to be created.

The second level, `john`/`joe`/`jack` & `hurricanes`/`crusaders`, are **identifiers**. Each identifier you specify
represents a new object and can be referenced in the PHP using `objFromFixture`


```php
$player = $this->objFromFixture('Player', 'jack');
```

The third and final level represents each individual object's fields.

A field can either be provided with raw data (such as the names for our Players), or we can define a relationship, as
seen by the fields prefixed with `=>`.

Each one of our Players has a relationship to a Team, this is shown with the `Team` field for each `Player` being set
to `=>Team.` followed by a team name.

<div class="info" markdown="1">
Take the player John in our example YAML, his team is the Hurricanes which is represented by `=>Team.hurricanes`. This
sets the `has_one` relationship for John with with the `Team` object `hurricanes`.
</div>

<div class="hint" markdown='1'>
Note that we use the name of the relationship (Team), and not the name of the
database field (TeamID).
</div>

<div class="hint" markdown='1'>
Also be aware the target of a relationship must be defined before it is referenced, for example the `hurricanes` team must appear in the fixture file before the line `Team: =>Team.hurricanes`.
</div>

This style of relationship declaration can be used for any type of relationship (i.e `has_one`, `has_many`, `many_many`).

We can also declare the relationships conversely. Another way we could write the previous example is:


```yml
Player:
  john:
    Name: John
  joe:
    Name: Joe
  jack:
    Name: Jack
Team:
  hurricanes:
    Name: Hurricanes
    Origin: Wellington
    Players: =>Player.john
  crusaders:
    Name: Crusaders
    Origin: Canterbury
    Players: =>Player.joe,=>Player.jack
```

The database is populated by instantiating `DataObject` objects and setting the fields declared in the `YAML`, then
calling `write()` on those objects. Take for instance the `hurricances` record in the `YAML`. It is equivalent to
writing:


```php
$team = new Team([
    'Name' => 'Hurricanes',
    'Origin' => 'Wellington'
]);

$team->write();

$team->Players()->add($john);
```

<div class="notice" markdown="1">
As the YAML fixtures will call `write`, any `onBeforeWrite()` or default value logic will be executed as part of the
test.
</div>

### Fixtures for namespaced classes

As of SilverStripe 4 you will need to use fully qualfied class names in your YAML fixture files. In the above examples, they belong to the global namespace so there is nothing requires, but if you have a deeper DataObject, or it has a relationship to models that are part of the framework for example, you will need to include their namespaces:


```yml
MyProject\Model\Player:
  john:
    Name: join
MyProject\Model\Team:
  crusaders:
    Name: Crusaders
    Origin: Canterbury
    Players: =>MyProject\Model\Player.john
```

<div class="notice" markdown="1">
If your tests are failing and your database has table names that follow the fully qualified class names, you've probably forgotten to implement `private static $table_name = 'Player';` on your namespaced class. This property was introduced in SilverStripe 4 to reduce data migration work. See [DataObject](api:SilverStripe\ORM\DataObject) for an example.
</div>

### Defining many_many_extraFields

`many_many` relations can have additional database fields attached to the relationship. For example we may want to
declare the role each player has in the team.


```php
use SilverStripe\ORM\DataObject;

class Player extends DataObject
{
    private static $db = [
        'Name' => 'Varchar(255)'
    ];

    private static $belongs_many_many = [
        'Teams' => 'Team'
    ];
}

class Team extends DataObject
{
    private static $db = [
        'Name' => 'Varchar(255)'
    ];

    private static $many_many = [
        'Players' => 'Player'
    ];

    private static $many_many_extraFields = [
        'Players' => [
            'Role' => "Varchar"
        ]
    ];
}
```

To provide the value for the `many_many_extraField` use the YAML list syntax.


```yml
Player:
  john:
    Name: John
  joe:
    Name: Joe
  jack:
    Name: Jack
Team:
  hurricanes:
    Name: The Hurricanes
    Players:
      - =>Player.john:
         Role: Captain

  crusaders:
    Name: The Crusaders
    Players:
      - =>Player.joe:
        Role: Captain
      - =>Player.jack:
        Role: Winger
```

## Fixture Factories

While manually defined fixtures provide full flexibility, they offer very little in terms of structure and convention.

Alternatively, you can use the [FixtureFactory](api:SilverStripe\Dev\FixtureFactory) class, which allows you to set default values, callbacks on object
creation, and dynamic/lazy value setting.

<div class="hint" markdown='1'>
`SapphireTest` uses `FixtureFactory` under the hood when it is provided with YAML based fixtures.
</div>

The idea is that rather than instantiating objects directly, we'll have a factory class for them. This factory can have
*blueprints* defined on it, which tells the factory how to instantiate an object of a specific type. Blueprints need a
name, which is usually set to the class it creates such as `Member` or `Page`.

Blueprints are auto-created for all available DataObject subclasses, you only need to instantiate a factory to start
using them.


```php
use SilverStripe\Core\Injector\Injector;

$factory = Injector::inst()->create('FixtureFactory');

$obj = $factory->createObject('Team', 'hurricanes');
```

In order to create an object with certain properties, just add a third argument:


```php
$obj = $factory->createObject('Team', 'hurricanes', [
    'Name' => 'My Value'
]);
```

<div class="warning" markdown="1">
It is important to remember that fixtures are referenced by arbitrary identifiers ('hurricanes'). These are internally
mapped to their database identifiers.
</div>

After we've created this object in the factory, `getId` is used to retrieve it by the identifier.


```php
$databaseId = $factory->getId('Team', 'hurricanes');
```

### Default Properties

Blueprints can be overwritten in order to customise their behavior. For example, if a Fixture does not provide a Team
name, we can set the default to be `Unknown Team`.


```php
$factory->define('Team', [
    'Name' => 'Unknown Team'
]);
```

### Dependent Properties

Values can be set on demand through anonymous functions, which can either generate random defaults, or create composite
values based on other fixture data.


```php
$factory->define('Member', [
    'Email' => function($obj, $data, $fixtures) {
        if(isset($data['FirstName']) {
            $obj->Email = strtolower($data['FirstName']) . '@example.com';
        }
    },
    'Score' => function($obj, $data, $fixtures) {
        $obj->Score = rand(0,10);
    }
)];
```

### Relations

Model relations can be expressed through the same notation as in the YAML fixture format described earlier, through the
`=>` prefix on data values.


```php
$obj = $factory->createObject('Team', 'hurricanes', [
    'MyHasManyRelation' => '=>Player.john,=>Player.joe'
]);
```

#### Callbacks

Sometimes new model instances need to be modified in ways which can't be expressed in their properties, for example to
publish a page, which requires a method call.


```php
$blueprint = Injector::inst()->create('FixtureBlueprint', 'Member');

$blueprint->addCallback('afterCreate', function($obj, $identifier, $data, $fixtures) {
    $obj->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
});

$page = $factory->define('Page', $blueprint);
```

Available callbacks:

 * `beforeCreate($identifier, $data, $fixtures)`
 * `afterCreate($obj, $identifier, $data, $fixtures)`

### Multiple Blueprints

Data of the same type can have variations, for example forum members vs. CMS admins could both inherit from the `Member`
class, but have completely different properties. This is where named blueprints come in. By default, blueprint names
equal the class names they manage.


```php
$memberBlueprint = Injector::inst()->create('FixtureBlueprint', 'Member', 'Member');

$adminBlueprint = Injector::inst()->create('FixtureBlueprint', 'AdminMember', 'Member');

$adminBlueprint->addCallback('afterCreate', function($obj, $identifier, $data, $fixtures) {
    if(isset($fixtures['Group']['admin'])) {
        $adminGroup = Group::get()->byId($fixtures['Group']['admin']);
        $obj->Groups()->add($adminGroup);
    }
});

$member = $factory->createObject('Member'); // not in admin group

$admin = $factory->createObject('AdminMember'); // in admin group
```

## Related Documentation

* [How to use a FixtureFactory](how_tos/fixturefactories/)

## API Documentation

* [FixtureFactory](api:SilverStripe\Dev\FixtureFactory)
* [FixtureBlueprint](api:SilverStripe\Dev\FixtureBlueprint)
