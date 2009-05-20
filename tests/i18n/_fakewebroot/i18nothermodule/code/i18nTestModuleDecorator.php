<?php
class i18nTestModuleDecorator extends DataObjectDecorator {
	function extraStatics() {
		return array(
			'db' => array(
				'MyExtraField' => 'Varchar'
			)
		);
	}
}
?>