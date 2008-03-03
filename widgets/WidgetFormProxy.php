<?php

class WidgetFormProxy extends Controller {
	function getFormOwner() {
		$widget = DataObject::get_by_id("Widget", $this->urlParams['ID']);
		
		// Put this in once widget->canView is implemented
		//if($widget->canView())
		return $widget;
		
	}
}