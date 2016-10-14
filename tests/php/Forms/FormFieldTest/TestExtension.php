<?php

namespace SilverStripe\Forms\Tests\FormFieldTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

/**
 * @package framework
 * @subpackage tests
 */
class TestExtension extends Extension implements TestOnly
{

	public function updateAttributes(&$attrs)
	{
		$attrs['extended'] = true;
	}

}
