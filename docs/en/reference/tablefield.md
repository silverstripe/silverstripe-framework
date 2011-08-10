# TableField

## Introduction

`[api:TableField]` behaves in the same manner as `[api:TableListField]`, however allows the editing of existing and adding of
new rows. The data is saved back by the surrounding form-saving (mostly EditForm->save).

See `[api:TableListField]` for more documentation on the base-class

## Usage

### Add hidden default data

Please use **TableField->setExtraData()** to specify additional (non-editable) data. You might use the following code
that shows the Player of Team with a particular Team ID and automatically saves new Players into this Team.

In this example, you'll note that we're setting TeamID to $this->ID.  This works well if you're including a TableField
as an editable field on a getCMSFields() call.

	:::php
	$myTableField = new TableField(
	  'MyTableField', // fieldName
	  'Player', // sourceType
	  array(
	    'FirstName'=>'First Name',
	    'Surname'=>'Surname'
	  ), // fieldList
	  array(
	    'FirstName'=>'TextField',
	    'Surname'=>'TextField'
	  ), // fieldTypes
	  null, // filterField (legacy)
	  "Player.TeamID",
	  $this->ID
	);
	// add some HiddenFields thats saved with each new row
	$myTableField->setExtraData(array(
	  'TeamID' => $this->ID ? $this->ID : '$RecordID'
	));


The '$RecordID' value is used when building forms that create new records.  It will be populated with whatever record id
is created.

### Row Transformation

You can apply a `[api:FormTransformation]` to any given field,
based on a eval()ed php-rule. You can access all columns on the generated DataObjects here.

	:::php
	$myTF->setTransformationConditions(array(
	  "PlayerName" => array(
	    "rule" => '$PlayerStatus == "Retired" || $PlayerStatus == "Injured"',
	    "transformation" => "performReadonlyTransformation"
	  )
	));


### Required Fields

Due to the nested nature of this fields dataset, you can't set any required columns as usual with the
`[api:RequiredFields]`** on the TableField-instance for this.
Note: You still have to attach some form of `[api:Validator]` to the form to trigger any validation on this field.


### Nested Table Fields

When you have `[api:TableField]` inside a `[api:ComplexTableField]`, the parent ID may not be known in your
getCMSFields() method.  In these cases, you can set a value to '$RecordID' in your `[api:TableField]` extra data, and this
will be populated with the newly created record id upon save.

## Known Issues

*  A `[api:TableField]` doesn't reload any submitted form-data if the saving is interrupted by a failed validation. After
refreshing the form with the validation-errors, the `[api:TableField]` will be blank again.
*  You can't add **visible default data** to columns in a `[api:TableField]`, please use *setExtraData*


## API Documentation

`[api:TableField]`