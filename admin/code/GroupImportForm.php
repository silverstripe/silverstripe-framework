<?php
/**
 * Imports {@link Group} records by CSV upload, as defined in
 * {@link GroupCsvBulkLoader}.
 * 
 * @package cms
 * @subpackage batchactions
 */
class GroupImportForm extends Form {
	
	/**
	 * @var Group Optional group relation
	 */
	protected $group;
	
	function __construct($controller, $name, $fields = null, $actions = null, $validator = null) {
		if(!$fields) {
			$helpHtml = _t(
				'GroupImportForm.Help1', 
				'<p>Import one or more groups in <em>CSV</em> format (comma-separated values). <small><a href="#" class="toggle-advanced">Show advanced usage</a></small></p>'
			);
			$helpHtml .= _t(
				'GroupImportForm.Help2', 
'<div class="advanced">
	<h4>Advanced usage</h4>
	<ul>
	<li>Allowed columns: <em>%s</em></li>
	<li>Existing groups are matched by their unique <em>Code</em> value, and updated with any new values from the imported file</li>
	<li>Group hierarchies can be created by using a <em>ParentCode</em> column.</li>
	<li>Permission codes can be assigned by the <em>PermissionCode</em> column. Existing permission codes are not cleared.</li>
	</ul>
</div>');
			
			$importer = new GroupCsvBulkLoader();
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

		$this->addExtraClass('cms');
		$this->addExtraClass('import-form');
	}
	
	function doImport($data, $form) {
		$loader = new GroupCsvBulkLoader();
		
		// load file
		$result = $loader->load($data['CsvFile']['tmp_name']);
		
		// result message
		$msgArr = array();
		if($result->CreatedCount()) $msgArr[] = _t(
			'GroupImportForm.ResultCreated', 'Created {count} groups',
			array('count' => $result->CreatedCount())
		);
		if($result->UpdatedCount()) $msgArr[] = _t(
			'GroupImportForm.ResultUpdated', 'Updated %d groups',
			array('count' => $result->UpdatedCount())
		);
		if($result->DeletedCount()) $msgArr[] = _t(
			'GroupImportForm.ResultDeleted', 'Deleted %d groups',
			array('count' => $result->DeletedCount())
		);
		$msg = ($msgArr) ? implode(',', $msgArr) : _t('MemberImportForm.ResultNone', 'No changes');
	
		$this->sessionMessage($msg, 'good');
		
		$this->controller->redirectBack();
	}
	
}
