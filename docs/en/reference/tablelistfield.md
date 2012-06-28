# TableListField

## Introduction

<div class="warning" markdown="1">
	This field is deprecated in favour of the new [GridField](/topics/grid-field) API.
</div>

Form field that embeds a list of `[api:DataObject]`s into a form, such as a member list or a file list.
Provides customizeable columns, record-deletion by ajax, paging, sorting, CSV-export, printing, input by
`[api:DataObject]` or raw SQL.

## Example

Here's an example of a full featured `[api:TableListField]` implementation. It features editing members in the database
directly as a button on each record, as well as filtering, and sorting. It also makes use of the 'export' permission,
allowing export of data as a CSV.

	:::php
	public function getReportField() {		
		$resultSet = new DataObjectSet();
		$filter = `;
		$sort = "Member.ID ASC";
		$join = `;
		$instance = singleton('Member');		
		$query = $instance->buildSQL($filter, $sort, null, $join);
		$query->groupby[] = 'Member.ID';
		
		$report = new TableListField(
			'CorporateReport',
			'Member',
			array(
				'ID' => 'ID',
				'FirstName' => 'First Name',
				'Surname' => 'Surname',
				'Email' => 'Email',
				'MembershipType' => 'Membership Type',
				'MembershipStatus' => 'Membership Status',
				'DateJoined' => 'Date Joined',
				'PaidUntil' => 'Paid Until',
				'Edit' => ''
			)
		);
			
		$report->setCustomQuery($query);
			
		$report->setFieldFormatting(array(
			'Email' => '<a href=\"mailto: $Email\" title=\"Email $FirstName\">$Email</a>',
			'Edit' => '<a href=\"admin/security/index/1?executeForm=EditForm&ID=1&ajax=1&action_callfieldmethod&fieldName=Members&ctf[childID]=$ID&ctf[ID]=1&ctf[start]=0&methodName=edit\"><img src=\"cms/images/edit.gif\" alt=\"Edit this member\" /></a>'
		));
			
		$report->setFieldCasting(array(
			'DateJoined' => 'Date->Nice',
			'PaidUntil' => 'Date->Nice'
		));
			
		$report->setShowPagination(true);
		if(isset($_REQUEST['printable'])) {
			$report->setPageSize(false);
		} else {
			$report->setPageSize(20);
		}
			
		$report->setPermissions(array(
			'export',
			'delete',
			'print'
		));
			
		return $report;
	}


For more information on each of the features used in the example, you can read below.

## Usage

### Source Input

	:::php
	// default: DataObject selection (e.g. all 'Product's)
	$myTableListField = new TableListField(
	  'MyName',
	  'Product',
	  array('Price', 'Code')
	);
	
	// custom DataObjectSet
	$myProducts = Product::get()->filter('Code', "MyCode");
	$myTableListField->setCustomSourceItems($myProducts);
	
	// custom SQL
	$customCsvQuery = singleton('Product')->buildSQL();
	$customCsvQuery->select[] = "CONCAT(col1,col2) AS MyCustomSQLColumn";
	$myTableListField->setCustomCsvQuery($customQuery);

`[api:TableListField]` also tries to resolve Component-relations(has_one, has_many) and custom getters automatically:

	:::php
	$myTableListField = new TableListField(
	  'MyName',
	  'Product',
	  array(
	   'Buyer.LastName',
	   'PriceWithShipping'
	  )
	);
	// Product.php Example
	class Product extends DataObject {
	  $has_one = array('Buyer'=>'Member');
	  public function getPriceWithShipping() {
	    return $this->Price + $this->Shipping;
	  }
	}


### Pagination

Paging works by AJAX, but also works without javascript on link-basis.

	:::php
	$myTableListField->setPageSize(100); // defaults to 20


### Sorting

The easiest method is to add the sorting criteria as a constructor parameter. Sorting should be applied manually, where
appropriate. Only direct columns from the produced SQL-query are supported. 

Example (sorting by "FirstName" column):

	:::php
	$report = new TableListField(
	  'CorporateReport', // name
	  'Member', // sourceClass
	  array(
	    'ID' => 'ID',
	    'FirstName' => 'First Name',
	    'LastName' => 'Last Name',
	  ), // fieldList
	  null, // sourceFilter
	  'FirstName' // sourceSort
	);


If you want to sort by custom getters in your `[api:DataObject]`, please reformulate them to a custom SQL column. This
restriction is needed to avoid performance-hits by caching and sorting potentially large datasets on PHP-level.

### Casting

Column-values can be casted, based on the casting-types available through DBObject (framework/core/model/fieldtypes).

	:::php
	$myTableListField->setFieldCasting(array(
	  "MyCustomDate"=>"Date",
	  "MyShortText"=>"Text->FirstSentence"
	));


### Permissions

Permissions vary in different `[api:TableListField]`-implementations, and are evaluated in the template.
By default, all listed permissions are enabled.

	:::php
	$myTableListField->setPermissions(array(
	  'delete',
	  'export',
	  'print'
	));


### Formatting

Specify custom formatting for fields, e.g. to render a link instead of pure text.
Caution: Make sure to escape special php-characters like in a normal php-statement.

	:::php
	$myTableListField->setFieldFormatting(array(
	  "myFieldName" => '<a href=\"custom-admin/$ID\">$ID</a>'
	));


### Highlighting

"Highlighting" is similiar to "Formatting", but applies to the whole row rather than a column.
Definitions for highlighting table-rows with a specific CSS-class. You can use all column-names
in the result of a query. Use in combination with {@setCustomQuery} to select custom properties and joined objects.

	:::php
	$myTableListField->setHighlightConditions(array(
	  array(
	    "rule" => '$Flag == "red"',
	    "class" => "red"
	  ),
	  array(
	    "rule" => '$Flag == "orange"',
	    "class" => "orange"
	  )
	));


### Export

Export works only to CSV currently, with following specs:

*  Line delimiter: "\n"
*  Separator: ";"
*  Column-quotes: none

	:::php
	$myTableListField->setPermissions(array('export'));
	$myTableListField->setFieldListCsv(array(
	  'Price' => 'Price',
	  'ItemCount' => 'Item Count',
	  'ModelNumber' => 'Model Number'
	));

You can influence the exported values by adjusting the generated SQL.

	:::php
	$customCsvQuery = singleton('Product')->buildSQL();
	$customCsvQuery->select[] = "CONCAT(col1,col2) AS MyCustomSQLColumn";
	$myTableListField->setCustomCsvQuery($customQuery);
	$myTableListField->setFieldListCsv(array(
	  'MyCustomSQLColumn'
	));


### Row-Summaries

You can summarize specific columns in your result-set. The term "summary" is used in a broad sense, you can also
implement averages etc.

	:::php
	$myTableListField->addSummary(
	  'Total Revenue and Sales Count',
	  array(
	    "Price" => array("sum","Currency->Nice"),
	    "ItemCount" => "sum"
	  )
	);

In `[api:TableListField]`-implementation, these summaries also react to changes in input-fields by javascript.
Available methods:

*  sum
*  avg

### Grouping

Used to group by a specific column in the `[api:DataObject]` and create partial summaries.
Please use only together with addSummary().
(Automatically disables sorting).

	:::php
	$myTableListField->groupByField = 'MyColumnName';


## Best Practices

### Custom Sorting

Please subclass `[api:TableListField]` to implement custom sorting, following the naming-convention
"`colFunction_<yourFunctionName>`".

	:::php
	class CustomTableListField extends TableListField {
	  // referenced through "dateAverage"
	  public function colFunction_dateAverage($values) {
	    // custom date summaries
	  }  
	}


### Adding Utility-functions

In case you want to perform utility-functions like "export" or "print" through action-buttons,
make sure to subclass Utility() which collates all possible actions.

### Customizing Look & Feel

You can exchange the used template, e.g. to change applied CSS-classes or the HTML-markup:

	:::php
	$myTableListField->setTemplate("MyPrettyTableListField");



## API Documentation

`[api:TableListField]`
