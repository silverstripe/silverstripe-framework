<?php
class i18nTestModuleDecorator extends DataObjectDecorator {
	function extraDBFields() {
		return array(
			'db' => array(
				'MyExtraField' => 'Varchar'
			)
		);
	}
}
?>