title: Indexes
summary: Add Indexes to your Data Model to optimize database queries.

# Indexes

It is sometimes desirable to add indexes to your data model, whether to optimize queries or add a uniqueness constraint 
to a field. This is done through the `DataObject::$indexes` map, which maps index names to descriptor arrays that 
represent each index. There's several supported notations:

	:::php
	<?php

	class MyObject extends DataObject {

		private static $indexes = array(
			'<column-name>' => true,
			'<index-name>' => array('type' => '<type>', 'value' => '"<column-name>"'),
			'<index-name>' => 'unique("<column-name>")'
		);
	}
	
The `<index-name>` can be an an arbitrary identifier in order to allow for more than one index on a specific database 
column. The "advanced" notation supports more `<type>` notations. These vary between database drivers, but all of them 
support the following:

 * `index`: Standard index
 * `unique`: Index plus uniqueness constraint on the value
 * `fulltext`: Fulltext content index

In order to use more database specific or complex index notations, we also support raw SQL for as a value in the 
`$indexes` definition. Keep in mind this will likely make your code less portable between databases.

**mysite/code/MyTestObject.php**

	:::php
	<?php

	class MyTestObject extends DataObject {

		private static $db = array(
			'MyField' => 'Varchar',
			'MyOtherField' => 'Varchar',
		);

		private static $indexes = array(
			'MyIndexName' => array(
				'type' => 'index', 
				'value' => '"MyField","MyOtherField"'
			)
		);
	}

## API Documentation

* [api:DataObject]