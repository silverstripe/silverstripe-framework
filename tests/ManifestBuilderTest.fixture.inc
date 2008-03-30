<?php

$filesystemFixture = array(
	'rahbeast/',
	'rahbeast/_config.php' => <<<PHP
<?php

global \$project;
\$project = 'rahbeast';

PHP
,
	'sapphire/',
	'sapphire/_config.php',
	'sapphire/MyClass.php' => <<<PHP
<?php

class MyClass extends Object {

}

class MyClass_Other extends DataObject implements Something {

}

class MyClass_Final extends DataObject implements Something, Else {

}

?>
PHP
,
	'sapphire/subdir/',
	'sapphire/subdir/SubDirClass.php' => <<<PHP
<?php

class SubDirClass extends Something implements Else, Other {

}
PHP
,
	'sapphire/subdir/SubDirClass.php',
	'otherdir/',
	'otherdir/OtherFile.php' => <<<PHP
<?php

class OtherFile extends Object {

}
PHP
,
);