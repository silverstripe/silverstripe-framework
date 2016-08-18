<?php

namespace SilverStripe\Forms;

/**
 * Readonly version of {@link InlineFormAction}.
 */
class InlineFormAction_ReadOnly extends FormField
{

	protected $readonly = true;

	/**
	 * @param array $properties
	 * @return string
	 */
	public function Field($properties = array())
	{
		return FormField::create_tag('input', array(
			'type' => 'submit',
			'name' => sprintf('action_%s', $this->name),
			'value' => $this->title,
			'id' => $this->ID(),
			'disabled' => 'disabled',
			'class' => 'action disabled ' . $this->extraClass,
		));
	}

	public function Title()
	{
		return false;
	}
}
