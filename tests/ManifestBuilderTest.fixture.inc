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

interface MyInterface {
	
}
interface OtherInterface {
	
}
class BaseClass {
	
}

/* This is a comment before */
class MyClass extends Object {

}

/* This is a comment between */

class MyClass_Other extends DataObject implements MyInterface {

}

class MyClass_Final extends DataObject implements MyInterface, OtherInterface {

}

/**
Here we have a class in a comment:
class MyClass_InComment extends DataObject {
	
}

This class definition isn't named correctly:
class UnrelatedCLass extends DataObject {
	
}

 */

// This is another class definition: class MyClass_InSlashSlashComment extends DataObject {

// Here's a class definition in a string:
\$string = <<<MYCODE

class MyClass_InHeredocString extends DataObject {
	
}

MYCODE;

// Other definitions inside strings:
\$string2 = 'class MyClass_InSingleQuoteString extends DataObject {
}'
\$string3 = "class MyClass_InDoubleQuoteString extends DataObject { 
}

I've included \"an escaped quote\" in this to ensure that it can handle that.
"

/* let's define a class between two strings to confirm that it gets discovered */
class MyClass_ClassBetweenTwoStrings extends DataObject {
	
	
}


\$string4 = "class MyClass_InDoubleQuoteString extends DataObject { 
}

I've included \"an escaped quote\" in this to ensure that it can handle that.
"

?>
PHP
,
	'sapphire/subdir/',
	'sapphire/subdir/SubDirClass.php' => <<<PHP
<?php

class SubDirClass extends BaseClass implements MyInterace, OtherInterface {

}
PHP
,
	'sapphire/subdir/SubDirClass.php',
	'otherdir/',
	'otherdir/OtherFile.php' => <<<PHP
<?php

class OtherFile extends BaseClass {

}
PHP
,
);