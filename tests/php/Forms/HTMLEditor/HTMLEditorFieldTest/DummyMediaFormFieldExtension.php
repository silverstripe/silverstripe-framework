<?php

namespace SilverStripe\Forms\Tests\HTMLEditor\HTMLEditorFieldTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\Form;

class DummyMediaFormFieldExtension extends Extension implements TestOnly
{
	public static $fields = null;
	public static $update_called = false;

	/**
	 * @param Form $form
	 */
	public function updateImageForm($form)
	{
		self::$update_called = true;
		self::$fields = $form->Fields();
	}
}
