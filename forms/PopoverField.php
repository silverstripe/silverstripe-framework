<?php

/**
 * Popup form action menu for "more options"
 *
 * Only works with react forms at the moment
 */
class PopoverField extends FieldGroup
{
	private static $cast = [
		'PopoverTitle' => 'HTMLText'
	];

	/**
	 * Use custom react component
	 *
	 * @var string
	 */
	protected $schemaComponent = 'PopoverField';

	/**
	 * Optional title on popup box
	 *
	 * @var string
	 */
	protected $popoverTitle = null;

	/**
	 * Get popup title
	 *
	 * @return string
	 */
	public function getPopoverTitle()
	{
		return $this->popoverTitle;
	}

	/**
	 * Set popup title
	 *
	 * @param string $popoverTitle
	 * @return $this
	 */
	public function setPopoverTitle($popoverTitle)
	{
		$this->popoverTitle = $popoverTitle;
		return $this;
	}

	public function getSchemaDataDefaults()
	{
		$schema = parent::getSchemaDataDefaults();
		if($this->getPopoverTitle()) {
			$data = [
				'popoverTitle' => $this->getPopoverTitle()
			];
			if(isset($schema['data'])) {
				$schema['data'] = array_merge($schema['data'], $data);
			} else {
				$schema['data'] = $data;
			}
		}
		return $schema;
	}
}
