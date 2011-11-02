<?php
/**
 * @deprecated Use {@link TreeDropdownField} or {@link TreeMultiselectField}
 * @package forms
 * @subpackage fields-relational
 */
class TreeSelectorField extends FormField {
	protected $sourceObject;
	
	function __construct($name, $title, $sourceObject = "Group") {
		$this->sourceObject = $sourceObject;
		parent::__construct($name, $title);
	}
	
	function Field() {
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/prototype/prototype.js");
		Requirements::javascript(SAPPHIRE_DIR . "/thirdparty/behaviour/behaviour.js");
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/prototype_improvements.js");

		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/TreeSelectorField.js");
		
		$fieldName = $this->name;
		if($this->form) {
			$record = $this->form->getRecord();
			if($record && $record->hasMethod($fieldName)) $items = $record->$fieldName();
		}
		if($items) {
			foreach($items as $item) {
				$titleArray[] =$item->Title;
				$idArray[] = $item->ID;
			}
			if($titleArray) {
				$itemList = implode(", ", $titleArray);
				$value = implode(",", $idArray);
			}
		}
		
		$id = $this->id();
		
		return <<<HTML
			<div class="TreeSelectorField">
				<input type="hidden" name="$this->name" value="$value" />
				<input type="button" class="edit" value="edit" />
				<span class="items">$itemList</span>
			</div>		
HTML;
	}
	
	/**
	 * Save the results into the form
	 */
	function saveInto(DataObject $record) {
		$fieldName = $this->name;
		$saveDest = $record->$fieldName();

		if($this->value) {
			$items = preg_split("/ *, */", trim($this->value));
		}

		$saveDest->setByIDList($items);
	}
	
	
	/**
	 * Return the site tree
	 */
	function gettree() {
		echo "<div class=\"actions\">
			<input type=\"button\" name=\"save\" value=\""._t('TreeSelectorField.SAVE', 'save')."\" />
			<input type=\"button\" name=\"cancel\" value=\""._t('TreeSelectorField.CANCEL', 'cancel')."\" />
		</div>";
		

		$obj = singleton($this->sourceObject);
		$obj->markPartialTree(10);
		
		$eval = '"<li id=\"selector-' . $this->name . '-$child->ID\" class=\"$child->class closed" . ($child->isExpanded() ? "" : " unexpanded") . "\"><a>" . $child->Title . "</a>"';
		echo $obj->getChildrenAsUL("class=\"tree\"", $eval, null, true);

	}

}

?>
