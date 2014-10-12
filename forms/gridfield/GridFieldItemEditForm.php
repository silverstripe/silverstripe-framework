<?php

class GridFieldItemEditForm extends Form
{
	/**
	 * Return the form's action attribute.
	 * This is build by adding an executeForm get variable to the parent controller's Link() value
	 *
	 * If the form action hasn't been otherwise customised then it will also add the query variables from the ModelAdmin
	 * if present. This is so that filtering on the model admin is preserved through multiple requests
	 *
	 * @return string
	 */
	public function FormAction()
	{
		if ($this->formActionPath) {
			return $this->formActionPath;
		} elseif ($this->controller->hasMethod("FormObjectLink")) {
			return $this->controller->FormObjectLink($this->name);
		} else {
			$filterVars = $this->controller->getRequest()->getVar('q');
			return Controller::join_links($this->controller->Link(), $this->name) . (!empty($filterVars) ? '?' . http_build_query(array('q' => $filterVars)) : '');
		}
	}
} 