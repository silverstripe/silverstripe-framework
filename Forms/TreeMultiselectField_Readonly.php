<?php

namespace SilverStripe\Forms;

class TreeMultiselectField_Readonly extends TreeMultiselectField
{

	protected $readonly = true;

	public function Field($properties = array())
	{
		$titleArray = $itemIDs = array();
		$titleList = $itemIDsList = "";
		if ($items = $this->getItems()) {
			foreach ($items as $item) {
				$titleArray[] = $item->Title;
			}
			foreach ($items as $item) {
				$itemIDs[] = $item->ID;
			}
			if ($titleArray) {
				$titleList = implode(", ", $titleArray);
			}
			if ($itemIDs) {
				$itemIDsList = implode(",", $itemIDs);
			}
		}

		$field = new ReadonlyField($this->name . '_ReadonlyValue', $this->title);
		$field->setValue($titleList);
		$field->setForm($this->form);

		$valueField = new HiddenField($this->name);
		$valueField->setValue($itemIDsList);
		$valueField->setForm($this->form);

		return $field->Field() . $valueField->Field();
	}
}
