<?php

namespace SilverStripe\Forms\HTMLEditor;

/**
 * Generate flash file embed
 */
class HTMLEditorField_Flash extends HTMLEditorField_File
{

	public function getFields()
	{
		$fields = parent::getFields();
		$fields->removeByName('CaptionText', true);
		return $fields;
	}
}
