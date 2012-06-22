<?php

/**
 * A class representing back actions.
 * See CMSMain.BatchActions.js on how to add custom javascript
 * functionality.
 * 
 * <code>
 * CMSMain::register_batch_action('publishitems', new CMSBatchAction('doPublish', 
 * 	_t('CMSBatchActions.PUBLISHED_PAGES', 'published %d pages')));
 * </code>
 * 
 * @package cms
 * @subpackage batchaction
 */
abstract class CMSBatchAction extends Object {
	
	protected $managedClass = 'SiteTree';
	
	/**
	 * The the text to show in the dropdown for this action
	 */
	abstract function getActionTitle();
	
	/**
	 * Run this action for the given set of pages.
	 * Return a set of status-updated JavaScript to return to the CMS.
	 */
	abstract function run(SS_List $objs);

	/**
	 * Helper method for responding to a back action request
	 * @param $successMessage string - The message to return as a notification.
	 * Can have up to two %d's in it. The first will be replaced by the number of successful
	 * changes, the second by the number of failures
	 * @param $status array - A status array like batchactions builds. Should be
	 * key => value pairs, the key can be any string: "error" indicates errors, anything
	 * else indicates a type of success. The value is an array. We don't care what's in it,
	 * we just use count($value) to find the number of items that succeeded or failed
	 */
	public function response($successMessage, $status) {
		$count = 0;
		$errors = 0;

		foreach($status as $k => $v) {
			if ($k == 'errors') $errors = count($v);
			else $count += count($v);
		}

		$response = Controller::curr()->getResponse();

		if($response) {
			$response->setStatusCode(
				200,
				sprintf($successMessage, $count, $errors)
			);
		}

		return Convert::raw2json($status);
	}

	/**
	 * Helper method for processing batch actions.
	 * Returns a set of status-updating JavaScript to return to the CMS.
	 *
	 * @param $objs The SS_List of objects to perform this batch action
	 * on.
	 * @param $helperMethod The method to call on each of those objects.
	 * @return JSON encoded map in the following format:
	 *  {
	 *     'modified': {
	 *       3: {'TreeTitle': 'Page3'},
	 *       5: {'TreeTitle': 'Page5'}
	 *     },
	 *     'deleted': {
	 *       // all deleted pages
	 *     }
	 *  }
	 */
	public function batchaction(SS_List $objs, $helperMethod, $successMessage, $arguments = array()) {
		$status = array('modified' => array(), 'error' => array());
		
		foreach($objs as $obj) {
			
			// Perform the action
			if (!call_user_func_array(array($obj, $helperMethod), $arguments)) {
				$status['error'][$obj->ID] = '';
			}
			
			// Now make sure the tree title is appropriately updated
			$publishedRecord = DataObject::get_by_id($this->managedClass, $obj->ID);
			if ($publishedRecord) {
				$status['modified'][$publishedRecord->ID] = array(
					'TreeTitle' => $publishedRecord->TreeTitle,
				);
			}
			$obj->destroy();
			unset($obj);
		}

		return $this->response($successMessage, $status);
	}

	

	/**
	 * Helper method for applicablePages() methods.  Acts as a skeleton implementation.
	 * 
	 * @param $ids The IDs passed to applicablePages
	 * @param $methodName The canXXX() method to call on each page to check if the action is applicable
	 * @param $checkStagePages Set to true if you want to check stage pages
	 * @param $checkLivePages Set to true if you want to check live pages (e.g, for deleted-from-draft)
	 */
	function applicablePagesHelper($ids, $methodName, $checkStagePages = true, $checkLivePages = true) {
		if(!is_array($ids)) user_error("Bad \$ids passed to applicablePagesHelper()", E_USER_WARNING);
		if(!is_string($methodName)) user_error("Bad \$methodName passed to applicablePagesHelper()", E_USER_WARNING);
		
		$applicableIDs = array();
		
		$SQL_ids = implode(', ', array_filter($ids, 'is_numeric'));
		$draftPages = DataObject::get(
			$this->managedClass, 
			sprintf(
				"\"%s\".\"ID\" IN (%s)",
				ClassInfo::baseDataClass($this->managedClass),
				$SQL_ids
			)
		);
		
		$onlyOnLive = array_fill_keys($ids, true);
		if($checkStagePages) {
			foreach($draftPages as $obj) {
				unset($onlyOnLive[$obj->ID]);
				if($obj->$methodName()) $applicableIDs[] = $obj->ID;
			}
		}
		
		if(Object::has_extension($this->managedClass, 'Versioned')) {
			// Get the pages that only exist on live (deleted from stage)
			if($checkLivePages && $onlyOnLive) {
				$SQL_ids = implode(', ', array_keys($onlyOnLive));
				$livePages = Versioned::get_by_stage(
					$this->managedClass, "Live", 
					sprintf(
						"\"%s\".\"ID\" IN (%s)",
						ClassInfo::baseDataClass($this->managedClass),
						$SQL_ids
					)
				);

				if($livePages) foreach($livePages as $obj) {
					if($obj->$methodName()) $applicableIDs[] = $obj->ID;
				}
			}
		}

		return $applicableIDs;
	}

	
	// if your batchaction has parameters, return a FieldList here
	function getParameterFields() {
		return false;
	}
	
	/**
	 * If you wish to restrict the batch action to some users, overload this function.
	 */
	function canView() {
		return true;
	}
}
