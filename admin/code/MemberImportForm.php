<?php
/**
 * Imports {@link Member} records by CSV upload, as defined in
 * {@link MemberCsvBulkLoader}.
 * 
 * @package cms
 * @subpackage batchactions
 */
class MemberImportForm extends Form {
	
	/**
	 * @var Group Optional group relation
	 */
	protected $group;
	
	function __construct($controller, $name, $fields = null, $actions = null, $validator = null) {
		if(!$fields) {
			$helpHtml = _t(
				'MemberImportForm.Help1', 
				'<p>Import users in <em>CSV format</em> (comma-separated values). <small><a href="#" class="toggle-advanced">Show advanced usage</a></small></p>'
			);
			$helpHtml .= _t(
				'MemberImportForm.Help2', 
'<div class="advanced">
	<h4>Advanced usage</h4>
	<ul>
	<li>Allowed columns: <em>%s</em></li>
	<li>Existing users are matched by their unique <em>Code</em> property, and updated with any new values from the imported file.</li>
	<li>Groups can be assigned by the <em>Groups</em> column. Groups are identified by their <em>Code</em> property, multiple groups can be separated by comma. Existing group memberships are not cleared.</li>
	</ul>
</div>');
			
			$importer = new MemberCsvBulkLoader();
			$importSpec = $importer->getImportSpec();
			$helpHtml = sprintf($helpHtml, implode(', ', array_keys($importSpec['fields'])));
			
			$fields = new FieldList(
				new LiteralField('Help', $helpHtml),
				$fileField = new FileField(
					'CsvFile', 
					_t(
						'SecurityAdmin_MemberImportForm.FileFieldLabel', 
						'CSV File <small>(Allowed extensions: *.csv)</small>'
					)
				)
			);
			$fileField->getValidator()->setAllowedExtensions(array('csv'));
		}
		
		if(!$actions) $actions = new FieldList(
			$importAction = new FormAction('doImport', _t('SecurityAdmin_MemberImportForm.BtnImport', 'Import from CSV'))
		);

		$importAction->addExtraClass('ss-ui-button');

		if(!$validator) $validator = new RequiredFields('CsvFile');
		
		parent::__construct($controller, $name, $fields, $actions, $validator);

		Requirements::javascript(FRAMEWORK_DIR . '/thirdparty/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/javascript/MemberImportForm.js');

		$this->addExtraClass('cms');
		$this->addExtraClass('import-form');
	}
	
	function doImport($data, $form) {
		$loader = new MemberCsvBulkLoader();
		
		// optionally set group relation
		if($this->group) $loader->setGroups(array($this->group));
		
		// load file
		$result = $loader->load($data['CsvFile']['tmp_name']);
		
		// result message
		$msgArr = array();
		if($result->CreatedCount()) $msgArr[] = _t(
			'MemberImportForm.ResultCreated', 'Created {count} members',
			array('count' => $result->CreatedCount())
		);
		if($result->UpdatedCount()) $msgArr[] = _t(
			'MemberImportForm.ResultUpdated', 'Updated {count} members',
			array('count' => $result->UpdatedCount())
		);
		if($result->DeletedCount()) $msgArr[] = _t(
			'MemberImportForm.ResultDeleted', 'Deleted %d members',
			array('count' => $result->DeletedCount())
		);
		$msg = ($msgArr) ? implode(',', $msgArr) : _t('MemberImportForm.ResultNone', 'No changes');
	
		$this->sessionMessage($msg, 'good');
		
		$this->controller->redirectBack();
	}
	
	/**
	 * @param $group Group
	 */
	function setGroup($group) {
		$this->group = $group;
	}
	
	/**
	 * @return Group
	 */
	function getGroup($group) {
		return $this->group;
	}
}
