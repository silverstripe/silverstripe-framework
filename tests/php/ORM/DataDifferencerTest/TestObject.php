<?php

namespace SilverStripe\ORM\Tests\DataDifferencerTest;

use SilverStripe\Assets\Image;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\ListboxField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Versioning\Versioned;

/**
 * @property string $Choices
 * @method Image Image()
 * @method HasOneRelationObject HasOneRelation()
 */
class TestObject extends DataObject implements TestOnly
{

	private static $table_name = 'DataDifferencerTest_Object';

	private static $extensions = array(
		Versioned::class
	);

	private static $db = array(
		'Choices' => "Varchar",
	);

	private static $has_one = array(
		'Image' => Image::class,
		'HasOneRelation' => HasOneRelationObject::class
	);

	public function getCMSFields()
	{
		$fields = parent::getCMSFields();
		$choices = array(
			'a' => 'a',
			'b' => 'b',
			'c' => 'c',
		);
		$listField = new ListboxField('Choices', 'Choices', $choices);
		$fields->push($listField);

		return $fields;
	}

}
