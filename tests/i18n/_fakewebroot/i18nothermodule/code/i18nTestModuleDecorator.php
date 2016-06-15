<?php

use SilverStripe\ORM\DataExtension;
class i18nTestModuleExtension extends DataExtension {

	public static $db = array(
		'MyExtraField' => 'Varchar'
	);

}
