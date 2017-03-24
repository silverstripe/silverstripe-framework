<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Object;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;

class ManyManyListExtensionTest extends SapphireTest
{

    protected static $fixture_file = 'ManyManyListExtensionTest.yml';

    protected static $extra_dataobjects = array(
        ManyManyListTest\IndirectPrimary::class,
        ManyManyListTest\Secondary::class,
        ManyManyListTest\SecondarySub::class
    );

    // Test that when one side of a many-many relationship is added by extension, both
    // sides still see the extra fields.
    public function testExtraFieldsViaExtension()
    {
        // This extends ManyManyListTest_Secondary with the secondary extension that adds the relationship back
        // to the primary. The instance from the fixture is ManyManyListTest_SecondarySub, deliberately a sub-class of
        // the extended class.
        Object::add_extension(ManyManyListTest\Secondary::class, ManyManyListTest\IndirectSecondaryExtension::class);

        // Test from the primary (not extended) to the secondary (which is extended)
        $primary = $this->objFromFixture(ManyManyListTest\IndirectPrimary::class, 'manymany_extra_primary');
        $secondaries = $primary->Secondary();
        $extraFields = $secondaries->getExtraFields();

        $this->assertTrue(count($extraFields) > 0, 'has extra fields');
        $this->assertTrue(isset($extraFields['DocumentSort']), 'has DocumentSort');

        // Test from the secondary (which is extended) to the primary (not extended)
        $secondary = $this->objFromFixture(ManyManyListTest\SecondarySub::class, 'manymany_extra_secondary');

        $primaries = $secondary->Primary();
        $extraFields = $primaries->getExtraFields();

        $this->assertTrue(count($extraFields) > 0, 'has extra fields');
        $this->assertTrue(isset($extraFields['DocumentSort']), 'has DocumentSort');
    }
}
