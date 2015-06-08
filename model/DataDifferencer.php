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
 * <% with Diff %>
 * <% loop ChangedFields %>
 *    <dt>$Title</dt>
 *    <dd>$Diff</dd>
 * <% end_loop %>
 * <% end_with %>
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
 *
 * @package framework
 * @subpackage misc
 */
class DataDifferencer extends ViewableData {
	protected $fromRecord;
	protected $toRecord;

	protected $ignoredFields = array("ID","Version","RecordID");

	/**
	 * Construct a DataDifferencer to show the changes between $fromRecord and $toRecord.
	 * If $fromRecord is null, this will represent a "creation".
	 *
	 * @param DataObject (Optional)
	 * @param DataObject
	 */
	public function __construct($fromRecord, DataObject $toRecord) {
		if(!$toRecord) user_error("DataDifferencer constructed without a toRecord", E_USER_WARNING);
		$this->fromRecord = $fromRecord;
		$this->toRecord = $toRecord;
		parent::__construct();
	}

	/**
	 * Specify some fields to ignore changes from.  Repeated calls are cumulative.
	 * @param $ignoredFields An array of field names to ignore.  Alternatively, pass the field names as
	 * separate args.
	 */
	public function ignoreFields($ignoredFields) {
		if(!is_array($ignoredFields)) $ignoredFields = func_get_args();
		$this->ignoredFields = array_merge($this->ignoredFields, $ignoredFields);
	}

	/**
	 * Get a DataObject with altered values replaced with HTML diff strings, incorporating
	 * <ins> and <del> tags.
	 */
	public function diffedData() {
		if($this->fromRecord) {
			$diffed = clone $this->fromRecord;
			$fields = array_keys($diffed->toMap() + $this->toRecord->toMap());
		} else {
			$diffed = clone $this->toRecord;
			$fields = array_keys($this->toRecord->toMap());
		}

		$hasOnes = array_merge($this->fromRecord->hasOne(), $this->toRecord->hasOne());

		// Loop through properties
		foreach($fields as $field) {
			if(in_array($field, $this->ignoredFields)) continue;
			if(in_array($field, array_keys($hasOnes))) continue;

			// Check if a field from-value is comparable
			$toField = $this->toRecord->obj($field);
			if(!($toField instanceof DBField)) continue;
			$toValue = $toField->forTemplate();

			// Show only to value
			if(!$this->fromRecord) {
				$diffed->setField($field, "<ins>{$toValue}</ins>");
				continue;
			}

			// Check if a field to-value is comparable
			$fromField = $this->fromRecord->obj($field);
			if(!($fromField instanceof DBField)) continue;
			$fromValue = $fromField->forTemplate();

			// Show changes between the two, if any exist
			if($fromValue != $toValue) {
				$diffed->setField($field, Diff::compareHTML($fromValue, $toValue));
			}
		}

		// Loop through has_one
		foreach($hasOnes as $relName => $relSpec) {
			if(in_array($relName, $this->ignoredFields)) continue;

			// Create the actual column name
			$relField = "{$relName}ID";
			$toTitle = '';
			if($this->toRecord->hasMethod($relName)) {
				$relObjTo = $this->toRecord->$relName();
				if($relObjTo) {
					$toTitle = ($relObjTo->hasMethod('Title') || $relObjTo->hasField('Title')) ? $relObjTo->Title : '';
				} else {
					$toTitle = '';
				}
			}

			if(!$this->fromRecord) {
				if($relObjTo) {
					if($relObjTo instanceof Image) {
						// Using relation name instead of database column name, because of FileField etc.
						// TODO Use CMSThumbnail instead to limit max size, blocked by DataDifferencerTest and GC
						// not playing nice with mocked images
						$diffed->setField($relName, "<ins>" . $relObjTo->getTag() . "</ins>");
					} else {
						$diffed->setField($relField, "<ins>" . $toTitle . "</ins>");
					}
				}
			} else if($this->fromRecord->$relField != $this->toRecord->$relField) {
				$fromTitle = '';
				if($this->fromRecord->hasMethod($relName)) {
					$relObjFrom = $this->fromRecord->$relName();
					if($relObjFrom) {
						$fromTitle = ($relObjFrom->hasMethod('Title') || $relObjFrom->hasField('Title')) ? $relObjFrom->Title : '';	
					} else {
						$fromTitle = '';
					}
					
				}
				if(isset($relObjFrom) && $relObjFrom instanceof Image) {
					// TODO Use CMSThumbnail (see above)
					$diffed->setField(
						// Using relation name instead of database column name, because of FileField etc.
						$relName,
						Diff::compareHTML($relObjFrom->getTag(), $relObjTo->getTag())
					);
				} else {
					// Set the field.
					$diffed->setField(
						$relField,
						Diff::compareHTML($fromTitle, $toTitle)
					);
				}

			}
		}

		return $diffed;
	}

	/**
	 * Get a SS_List of the changed fields.
	 * Each element is an array data containing
	 *  - Name: The field name
	 *  - Title: The human-readable field title
	 *  - Diff: An HTML diff showing the changes
	 *  - From: The older version of the field
	 *  - To: The newer version of the field
	 */
	public function ChangedFields() {
		$changedFields = new ArrayList();

		if($this->fromRecord) {
			$base = $this->fromRecord;
			$fields = array_keys($this->fromRecord->toMap());
		} else {
			$base = $this->toRecord;
			$fields = array_keys($this->toRecord->toMap());
		}

		foreach($fields as $field) {
			if(in_array($field, $this->ignoredFields)) continue;

			if(!$this->fromRecord || $this->fromRecord->$field != $this->toRecord->$field) {
				// Only show HTML diffs for fields which allow HTML values in the first place
				$fieldObj = $this->toRecord->dbObject($field);
				if($this->fromRecord) {
					$fieldDiff = Diff::compareHTML(
						$this->fromRecord->$field,
						$this->toRecord->$field,
						(!$fieldObj || $fieldObj->stat('escape_type') != 'xml')
					);
				} else {
					if($fieldObj && $fieldObj->stat('escape_type') == 'xml') {
						$fieldDiff = "<ins>" . $this->toRecord->$field . "</ins>";
					} else {
						$fieldDiff = "<ins>" . Convert::raw2xml($this->toRecord->$field) . "</ins>";
					}
				}
				$changedFields->push(new ArrayData(array(
					'Name' => $field,
					'Title' => $base->fieldLabel($field),
					'Diff' => $this->fromRecord
						? Diff::compareHTML($this->fromRecord->$field, $this->toRecord->$field)
						: "<ins>" . $this->toRecord->$field . "</ins>",
					'From' => $this->fromRecord ? $this->fromRecord->$field : null,
					'To' => $this->toRecord ? $this->toRecord->$field : null,
				)));
			}
		}

		return $changedFields;
	}

	/**
	 * Get an array of the names of every fields that has changed.
	 * This is simpler than {@link ChangedFields()}
	 */
	public function changedFieldNames() {
		$diffed = clone $this->fromRecord;
		$fields = array_keys($diffed->toMap());

		$changedFields = array();

		foreach($fields as $field) {
			if(in_array($field, $this->ignoredFields)) continue;
			if($this->fromRecord->$field != $this->toRecord->$field) {
				$changedFields[] = $field;
			}
		}

		return $changedFields;
	}
}
