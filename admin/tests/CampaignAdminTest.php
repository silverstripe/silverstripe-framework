<?php

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Admin\CampaignAdmin;
use SilverStripe\ORM\Versioning\ChangeSet;
use SilverStripe\ORM\UnexpectedDataException;

class CampaignAdminTest extends SapphireTest
{

	/**
	 * Call a protected method on an object via reflection
	 *
	 * @param object $object The object to call the method on
	 * @param string $method The name of the method
	 * @param array $args The arguments to pass to the method
	 */
	function callProtectedMethod($object, $method, $args = []) {
	  $class = new ReflectionClass(get_class($object));
	  $methodObj = $class->getMethod($method);
	  $methodObj->setAccessible(true);
	  return $methodObj->invokeArgs($object, $args);
	}

	function testInvalidDataHandling() {
		$changeset = new CampaignAdminTest_InvalidChangeSet();
		$admin = new CampaignAdmin();

		$result = $this->callProtectedMethod($admin, 'getChangeSetResource', [$changeset] );
		$this->assertEquals('Corrupt database! bad data' , $result['Description']);
	}
}

class CampaignAdminTest_InvalidChangeSet extends ChangeSet
{
	function sync()
	{
		throw new UnexpectedDataException("bad data");
	}
}
