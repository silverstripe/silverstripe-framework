<?php

/**
 * Utility class to render views of the differences between two data objects (or two versions of the
 * same data object).
 * 
 * Construcing a diff object is done as follows:
 * <code>
 * $fromRecord = Versioned::get_version('SiteTree', $pageID, $fromVersion);
 * $toRecord = Versioned::get_version('SiteTree, $pageID, $toVersion);
 * $diff = new DataDifferencer($fromRecord, $toRecord);
 * </code>
 * 
 * And then it can be used in a number of ways.  You can use the ChangedFields() method in a template:
 * <pre>
 * <dl class="diff">
 * <% control Diff.ChangedFields %>
 *    <dt>$Title</dt>
 *    <dd>$Diff</dd>
 * <% end_control %>
 * </dl>
 * </pre>
 * 
 * Or you can get the diff'ed content as another DataObject, that you can insert into a form.
 * <code>
 * $form->loadDataFrom($diff->diffedData());
 * </code>
 * 
 * If there are fields whose changes you aren't interested in, you can ignore them like so:
 * <code>
 * $diff->ignoreFields('AuthorID', 'Status');
 * </code>
 */
class DataDifferencer extends ViewableData {
	protected $fromRecord;
	protected $toRecord;
	
	protected $ignoredFields = array("ID","Version","RecordID");
	
	function __construct($fromRecord, $toRecord) {
		$this->fromRecord = $fromRecord;
		$this->toRecord = $toRecord;
	}
	
	/**
	 * Specify some fields to ignore changes from.  Repeated calls are cumulative.
	 * @param $ignoredFields An array of field names to ignore.  Alternatively, pass the field names as
	 * separate args.
	 */
	function ignoreFields($ignoredFields) {
		if(!is_array($ignoredFields)) $ignoredFields = func_get_args();
		$this->ignoredFields = array_merge($this->ignoredFields, $ignoredFields);
	}
	
	function diffedData() {
		$diffed = clone $this->fromRecord;
		$fields = array_keys($diffed->getAllFields());
		
		foreach($fields as $field) {
			if(in_array($field, $this->ignoredFields)) continue;
			
			if($this->fromRecord->$field != $this->toRecord->$field) {			
				$diffed->$field = Diff::compareHTML($this->fromRecord->$field, $this->toRecord->$field);
			}
		}
		
		return $diffed;
	}
	
	function ChangedFields() {
		$changedFields = new DataObjectSet();
		$fields = array_keys($this->fromRecord->getAllFields());
		
		foreach($fields as $field) {
			if(in_array($field, $this->ignoredFields)) continue;

			if($this->fromRecord->$field != $this->toRecord->$field) {			
				$changedFields->push(new ArrayData(array(
					'Title' => $this->fromRecord->fieldLabel($field),
					'Diff' => Diff::compareHTML($this->fromRecord->$field, $this->toRecord->$field),
				)));
			}
		}
		
		return $changedFields;
	}
}