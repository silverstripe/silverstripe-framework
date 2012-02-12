<?php
class i18nTestModuleExtension extends DataExtension {
	function extraStatics($class=null, $extension=null) {
		return array(
			'db' => array(
				'MyExtraField' => 'Varchar'
			)
		);
	}
}
