<?php

namespace SilverStripe\Admin\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Admin\CampaignAdmin;
use ReflectionClass;

class CampaignAdminTest extends SapphireTest
{
    protected $extraDataObjects = [
        CampaignAdminTest\InvalidChangeSet::class,
    ];

    /**
     * Call a protected method on an object via reflection
     *
     * @param object $object The object to call the method on
     * @param string $method The name of the method
     * @param array $args The arguments to pass to the method
     * @return mixed
     */
    protected function callProtectedMethod($object, $method, $args = [])
    {
        $class = new ReflectionClass(get_class($object));
        $methodObj = $class->getMethod($method);
        $methodObj->setAccessible(true);
        return $methodObj->invokeArgs($object, $args);
    }

    public function testInvalidDataHandling()
    {
        $changeset = new CampaignAdminTest\InvalidChangeSet();
        $admin = new CampaignAdmin();

        $result = $this->callProtectedMethod($admin, 'getChangeSetResource', [$changeset]);
        $this->assertEquals('Corrupt database! bad data', $result['Description']);
    }
}
