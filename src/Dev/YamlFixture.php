<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use Symfony\Component\Yaml\Parser;
use InvalidArgumentException;

/**
 * Uses Symfony's YAML component to parse a YAML document (see http://yaml.org).
 * YAML is a simple markup languages that uses tabs and colons instead of the more verbose XML tags,
 * and because of this much better for developers creating files by hand.
 *
 * The contents of the YAML file are broken into three levels:
 * - Top level: class names - Page and ErrorPage. This is the name of the dataobject class that should be created.
 *   The fact that ErrorPage is actually a subclass is irrelevant to the system populating the database.
 *   Each identifier you specify delimits a new database record.
 *   This means that every record needs to have an identifier, whether you use it or not.
 * - Third level: fields - each field for the record is listed as a 3rd level entry.
 *   In most cases, the field's raw content is provided.
 *   However, if you want to define a relationship, you can do so using "=>"
 *
 * There are a couple of lines like this:
 * <code>
 * Parent: =>Page.about
 * </code>
 * This will tell the system to set the ParentID database field to the ID of the Page object with the identifier
 * 'about'. This can be used on any has-one or many-many relationship.
 * Note that we use the name of the relationship (Parent), and not the name of the database field (ParentID)
 *
 * On many-many relationships, you should specify a comma separated list of values.
 * <code>
 * MyRelation: =>Class.inst1,=>Class.inst2,=>Class.inst3
 * </code>
 *
 * An crucial thing to note is that the YAML file specifies DataObjects, not database records.
 * The database is populated by instantiating DataObject objects, setting the fields listed, and calling write().
 * This means that any onBeforeWrite() or default value logic will be executed as part of the test.
 * This forms the basis of our testURLGeneration() test above.
 *
 * For example, the URLSegment value of Page.staffduplicate is the same as the URLSegment value of Page.staff.
 * When the fixture is set up, the URLSegment value of Page.staffduplicate will actually be my-staff-2.
 *
 * Finally, be aware that requireDefaultRecords() is not called by the database populator -
 * so you will need to specify standard pages such as 404 and home in your YAML file.
 *
 * <code>
 * Page:
 *    home:
 *       Title: Home
 *    about:
 *       Title: About Us
 *    staff:
 *       Title: Staff
 *       URLSegment: my-staff
 *       Parent: =>Page.about
 *    staffduplicate:
 *       Title: Staff
 *       URLSegment: my-staff
 *       Parent: =>Page.about
 *    products:
 *       Title: Products
 * ErrorPage:
 *    404:
 *      Title: Page not Found
 *      ErrorCode: 404
 * </code>
 */
class YamlFixture
{
    use Injectable;

    /**
     * Absolute path to the .yml fixture file
     *
     * @var string
     */
    protected $fixtureFile;

    /**
     * String containing fixture
     *
     * @var String
     */
    protected $fixtureString;

    /**
     * @param string $fixture Absolute file path, or relative path to {@link Director::baseFolder()}
     */
    public function __construct($fixture)
    {
        if (false !== strpos($fixture ?? '', "\n")) {
            $this->fixtureString = $fixture;
        } else {
            if (!Director::is_absolute($fixture)) {
                $fixture = Director::baseFolder() . '/' . $fixture;
            }

            if (!file_exists($fixture ?? '')) {
                throw new InvalidArgumentException('YamlFixture::__construct(): Fixture path "' . $fixture
                    . '" not found');
            }

            $this->fixtureFile = $fixture;
        }
    }

    /**
     * @return String Absolute file path
     */
    public function getFixtureFile()
    {
        return $this->fixtureFile;
    }

    /**
     * @return String Fixture string
     */
    public function getFixtureString()
    {
        return $this->fixtureString;
    }

    /**
     * Persists the YAML data in a FixtureFactory,
     * which in turn saves them into the database.
     * Please use the passed in factory to access the fixtures afterwards.
     *
     * @param  FixtureFactory $factory
     */
    public function writeInto(FixtureFactory $factory)
    {
        $parser = new Parser();
        if (isset($this->fixtureString)) {
            $fixtureContent = $parser->parse($this->fixtureString);
        } else {
            if (!file_exists($this->fixtureFile ?? '') || is_dir($this->fixtureFile ?? '')) {
                return;
            }

            $contents = file_get_contents($this->fixtureFile ?? '');
            $fixtureContent = $parser->parse($contents);

            if (!$fixtureContent) {
                return;
            }
        }

        foreach ($fixtureContent as $class => $items) {
            foreach ($items as $identifier => $data) {
                if (ClassInfo::exists($class)) {
                    $factory->createObject($class, $identifier, $data);
                } else {
                    $factory->createRaw($class, $identifier, $data);
                }
            }
        }
    }
}
