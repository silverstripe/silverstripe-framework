<?php

use SilverStripe\Dev\TestOnly;

class ConfigStaticManifestTestMyObject implements TestOnly {
	static private $db = [
		'Name' => 'Varchar',
		'Description' => 'Text',
	];
}
