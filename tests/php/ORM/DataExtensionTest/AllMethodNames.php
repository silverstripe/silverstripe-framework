<?php

namespace SilverStripe\ORM\Tests\DataExtensionTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class AllMethodNames extends DataExtension implements TestOnly
{
	public function allMethodNames()
	{
		return array(
			strtolower('getTestValueWith' . $this->owner->ClassName)
		);
	}
}
