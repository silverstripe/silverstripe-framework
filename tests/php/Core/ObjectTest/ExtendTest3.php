<?php

namespace SilverStripe\Core\Tests\ObjectTest;

use SilverStripe\Core\Extension;

class ExtendTest3 extends Extension
{
	public function extendableMethod($argument = null)
	{
		return "ExtendTest3($argument)";
	}
}
