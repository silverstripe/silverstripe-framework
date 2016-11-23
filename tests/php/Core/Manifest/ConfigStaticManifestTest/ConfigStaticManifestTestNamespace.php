<?php

namespace config\staticmanifest;
use SilverStripe\Dev\TestOnly;


class NamespaceTest implements TestOnly {
	static private $db = array(
		'Name' => 'Varchar',
		'Description' => 'Text',
	);
}
