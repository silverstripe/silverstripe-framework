<?php
/**
 * Action that clears all fields on a form.
 * Inserts an input tag with type=reset.
 * @package forms
 * @subpackage actions
 */
class ResetFormAction extends FormAction {
	
	function Field() {
		$attributes = array(
			'class' => 'action' . ($this->extraClass() ? $this->extraClass() : ''),
			'id' => $this->id(),
			'type' => 'reset',
			'name' => $this->action,
		);
			
		if($this->isReadonly()) {
			$attributes['disabled'] = 'disabled';
			$attributes['class'] = $attributes['class'] . ' disabled';
		}
			
		$attributes['title'] = ($this->description) ? $this->description : ($this->dontEscape) ? $this->Title() : $this->attrTitle();
		
		if($this->useButtonTag) {
			return $this->createTag('button', $attributes, $this->attrTitle());
		}

		return $this->createTag('input', $attributes);
	}
	
}
?>