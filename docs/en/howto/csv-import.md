# Import CSV data

## Introduction

CSV import can be easily achieved through PHP's built-in `fgetcsv()` method,
but this method doesn't know anything about your datamodel. In SilverStripe,
this can be handled through the a specialized CSV importer class that can
be customized to fit your data.

## The CsvBulkLoader class

The [api:CsvBulkLoader] class facilitate complex CSV-imports by defining column-mappings and custom converters. 
It uses PHP's built-in `fgetcsv()` function to process CSV input, and accepts a file handle as an input.

Feature overview:

*  Custom column mapping
*  Auto-detection of CSV-header rows
*  Duplicate detection based on custom criteria
*  Automatic generation of relations based on one or more columns in the CSV-Data
*  Definition of custom import methods (e.g. for date conversion or combining multiple columns)
*  Optional deletion of existing records if they're not present in the CSV-file
*  Results grouped by "imported", "updated" and "deleted"

## Usage

You can use the CsvBulkLoader without subclassing or other customizations, if the column names
in your CSV file match `$db` properties in your dataobject. E.g. a simple import for the
`[api:Member]` class could have this data in a file:

	FirstName,LastName,Email
	Donald,Duck,donald@disney.com
	Daisy,Duck,daisy@disney.com

The loader would be triggered through the `load()` method:

	:::php
	$loader = new CsvBulkLoader('Member');
	$result = $loader->load('<my-file-path>');

By the way, you can import `[api:Member]` and `[api:Group]` data through `http://localhost/admin/security`
interface out of the box.

## Import through ModelAdmin

The simplest way to use [api:CsvBulkLoader] is through a [api:ModelAdmin] interface - you get an upload form out of the box.

	:::php
	<?php
	class PlayerAdmin extends ModelAdmin {
	   static $managed_models = array(
	      'Player'
	   );
	   static $model_importers = array(
	      'Player' => 'PlayerCsvBulkLoader', 
	   );
	   static $url_segment = 'players';
	}
	?>

The new admin interface will be available under `http://localhost/admin/players`, the import form is located
below the search form on the left.

## Import through a custom controller

You can have more customized logic and interface feedback through a custom controller. Let's create a simple upload form (which is used for `MyDataObject` instances). You can access it through  `http://localhost/MyController/?flush=all`.

	:::php
	<?php
	class MyController extends Controller {

		static $allowed_actions = array('Form');
		
		protected $template = "BlankPage";
		
		function Link($action = null) {
			return Controller::join_links('MyController', $action);
		}
		
		function Form() {
			$form = new Form(
				$this,
				'Form',
				new FieldSet(
					new FileField('CsvFile', false)
				),
				new FieldSet(
					new FormAction('doUpload', 'Upload')
				),
				new RequiredFields()
			);
			return $form;
		}
		
		function doUpload($data, $form) {
			$loader = new CsvBulkLoader('MyDataObject');
			$results = $loader->load($_FILES['CsvFile']['tmp_name']);
			$messages = array();
			if($results->CreatedCount()) $messages[] = sprintf('Imported %d items', $results->CreatedCount());
			if($results->UpdatedCount()) $messages[] = sprintf('Updated %d items', $results->UpdatedCount());
			if($results->DeletedCount()) $messages[] = sprintf('Deleted %d items', $results->DeletedCount());
			if(!$messages) $messages[] = 'No changes';
			$form->sessionMessage(implode(', ', $messages), 'good');
	
			return $this->redirectBack();
		}
	}

Note: This interface is not secured, consider using [api:Permission::check()] to limit the controller to users
with certain access rights.

## Column mapping and relation import

We're going to use our knowledge from the previous example to import a more sophisticated CSV file.

Sample CSV Content

	"SpielerNummer","Name","Geburtsdatum","Gruppe"
	11,"John Doe",1982-05-12,"FC Bayern"
	12,"Jane Johnson", 1982-05-12,"FC Bayern"
	13,"Jimmy Dole",,"Schalke 04"


Datamodel for Player

	:::php
	<?php
	class Player extends DataObject {
	   static $db = array(
	      'PlayerNumber' => 'Int',
	      'FirstName' => 'Text', 
	      'LastName' => 'Text', 
	      'Birthday' => 'Date', 
	   );
	   static $has_one = array(
	      'Team' => 'FootballTeam'
	   );
	}
	?>


Datamodel for FootballTeam:

	:::php
	<?php
	class FootballTeam extends DataObject {
	   static $db = array(
	      'Title' => 'Text', 
	   );
	   static $has_many = array(
	      'Players' => 'Player'
	   );
	}
	?>


Sample implementation of a custom loader. Assumes a CSV-file in a certain format (see below).

*  Converts property names
*  Splits a combined "Name" fields from the CSV-data into `FirstName` and `Lastname` by a custom importer method
*  Avoids duplicate imports by a custom `$duplicateChecks` definition
*  Creates `Team` relations automatically based on the `Gruppe` column in the CSV data


	:::php
	<?php
	class PlayerCsvBulkLoader extends CsvBulkLoader {
	   public $columnMap = array(
	      'Number' => 'PlayerNumber', 
	      'Name' => '->importFirstAndLastName', 
	      'Geburtsdatum' => 'Birthday', 
	      'Gruppe' => 'Team.Title', 
	   );
	   public $duplicateChecks = array(
	      'SpielerNummer' => 'PlayerNumber'
	   );
	   public $relationCallbacks = array(
	      'Team.Title' => array(
	         'relationname' => 'Team',
	         'callback' => 'getTeamByTitle'
	      )
	   );
	   static function importFirstAndLastName(&$obj, $val, $record) {
	      $parts = explode(' ', $val);
	      if(count($parts) != 2) return false;
	      $obj->FirstName = $parts[0];
	      $obj->LastName = $parts[1];
	   }
	   static function getTeamByTitle(&$obj, $val, $record) {
	      $SQL_val = Convert::raw2sql($val);
	      return DataObject::get_one(
	         'FootballTeam', "Title = '{$SQL_val}'"
	      );
	   }
	}
	?>

## Related

*  [api:CsvParser]
*  [api:ModelAdmin]
