title: Import CSV Data through a Controller

# Import CSV Data through a Controller

You can have more customized logic and interface feedback through a custom controller. Let's create a simple upload 
form (which is used for `MyDataObject` instances). You can access it through 
`http://yoursite.com/MyController/?flush=all`.

	:::php
	<?php

	class MyController extends Controller {

		private static $allowed_actions = array(
			'Form'
		);

		protected $template = "BlankPage";

		public function Link($action = null) {
			return Controller::join_links('MyController', $action);
		}

		public function Form() {
			$form = new Form(
				$this,
				'Form',
				new FieldList(
					new FileField('CsvFile', false)
				),
				new FieldList(
					new FormAction('doUpload', 'Upload')
				),
				new RequiredFields()
			);
			return $form;
		}

		public function doUpload($data, $form) {
			$loader = new CsvBulkLoader('MyDataObject');
			$results = $loader->load($_FILES['CsvFile']['tmp_name']);
			$messages = array();

			if($results->CreatedCount()) {
				$messages[] = sprintf('Imported %d items', $results->CreatedCount());
			}

			if($results->UpdatedCount()) {
				$messages[] = sprintf('Updated %d items', $results->UpdatedCount());
			}

			if($results->DeletedCount()) {
				$messages[] = sprintf('Deleted %d items', $results->DeletedCount());
			}

			if(!$messages) {
				$messages[] = 'No changes';
			}

			$form->sessionMessage(implode(', ', $messages), 'good');

			return $this->redirectBack();
		}
	}

<div class="alert" markdown="1">
This interface is not secured, consider using [api:Permission::check()] to limit the controller to users with certain 
access rights.
</div>
