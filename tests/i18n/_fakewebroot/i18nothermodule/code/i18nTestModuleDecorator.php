<?php
class i18nTestModuleExtension extends DataExtension {
	function extraStatics() {
		return array(
			'db' => array(
				'MyExtraField' => 'Varchar'
			)
		);
	}
}
?>