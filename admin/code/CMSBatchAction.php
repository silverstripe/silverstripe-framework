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
	/**
	 * The the text to show in the dropdown for this action
	 */
	abstract function getActionTitle();
	
	/**
	 * Run this action for the given set of pages.
	 * Return a set of status-updated JavaScript to return to the CMS.
	 */
	abstract function run(DataObjectSet $pages);
	
	/**
	 * Helper method for processing batch actions.
	 * Returns a set of status-updating JavaScript to return to the CMS.
	 *
	 * @param $pages The DataObjectSet of SiteTree objects to perform this batch action
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
	public function batchaction(DataObjectSet $pages, $helperMethod, $successMessage, $arguments = array()) {
		$status = array('modified' => array(), 'error' => array());
		
		foreach($pages as $page) {
			
			// Perform the action
			if (!call_user_func_array(array($page, $helperMethod), $arguments)) {
				$status['error'][$page->ID] = '';
			}
			
			// Now make sure the tree title is appropriately updated
			$publishedRecord = DataObject::get_by_id('SiteTree', $page->ID);
			if ($publishedRecord) {
				$status['modified'][$publishedRecord->ID] = array(
					'TreeTitle' => $publishedRecord->TreeTitle,
				);
			}
			$page->destroy();
			unset($page);
		}

		$response = Controller::curr()->getResponse();
		if($response) {
			$response->setStatusCode(
				200, 
				sprintf($successMessage, $pages->Count(), count($status['error']))
			);
		}

		return Convert::raw2json($status);
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
		$draftPages = DataObject::get("SiteTree", "\"SiteTree\".\"ID\" IN ($SQL_ids)");
		
		$onlyOnLive = array_fill_keys($ids, true);
		if($checkStagePages) {
			foreach($draftPages as $page) {
				unset($onlyOnLive[$page->ID]);
				if($page->$methodName()) $applicableIDs[] = $page->ID;
			}
		}
		
		// Get the pages that only exist on live (deleted from stage)
		if($checkLivePages && $onlyOnLive) {
			$SQL_ids = implode(', ', array_keys($onlyOnLive));
			$livePages = Versioned::get_by_stage("SiteTree", "Live", "\"SiteTree\".\"ID\" IN ($SQL_ids)");
		
			if($livePages) foreach($livePages as $page) {
				if($page->$methodName()) $applicableIDs[] = $page->ID;
			}
		}

		return $applicableIDs;
	}

	
	// if your batchaction has parameters, return a fieldset here
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

/**
 * Publish items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Publish extends CMSBatchAction {
	function getActionTitle() {
		return _t('CMSBatchActions.PUBLISH_PAGES', 'Publish');
	}
	function getDoingText() {
		return _t('CMSBatchActions.PUBLISHING_PAGES', 'Publishing selected pages');
	}

	function run(DataObjectSet $pages) {
		return $this->batchaction($pages, 'doPublish',
			_t('CMSBatchActions.PUBLISHED_PAGES', 'Published %d pages, %d failures')
		);
	}

	function applicablePages($ids) {
		return $this->applicablePagesHelper($ids, 'canPublish', true, false);
	}
}

/**
 * Un-publish items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Unpublish extends CMSBatchAction {
	function getActionTitle() {
		return _t('CMSBatchActions.UNPUBLISH_PAGES', 'Un-publish');
	}
	function getDoingText() {
		return _t('CMSBatchActions.UNPUBLISHING_PAGES', 'Un-publishing selected pages');
	}

	function run(DataObjectSet $pages) {
		return $this->batchaction($pages, 'doUnpublish',
			_t('CMSBatchActions.UNPUBLISHED_PAGES', 'Un-published %d pages')
		);
	}
}

/**
 * Delete items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_Delete extends CMSBatchAction {
	function getActionTitle() {
		return _t('CMSBatchActions.DELETE_DRAFT_PAGES', 'Delete from draft site');
	}
	function getDoingText() {
		return _t('CMSBatchActions.DELETING_DRAFT_PAGES', 'Deleting selected pages from the draft site');
	}

	function run(DataObjectSet $pages) {
		$status = array(
			'modified'=>array(),
			'deleted'=>array(),
			'error'=>array()
		);
		
		foreach($pages as $page) {
			$id = $page->ID;
			
			// Perform the action
			if($page->canDelete()) $page->delete();
			else $status['error'][$page->ID] = true;

			// check to see if the record exists on the live site, 
			// if it doesn't remove the tree node
			$liveRecord = Versioned::get_one_by_stage( 'SiteTree', 'Live', "\"SiteTree\".\"ID\"=$id");
			if($liveRecord) {
				$liveRecord->IsDeletedFromStage = true;
				$status['modified'][$liveRecord->ID] = array(
					'TreeTitle' => $liveRecord->TreeTitle,
				);
			} else {
				$status['deleted'][$id] = array();
			}

		}

		return Convert::raw2json($status);
	}

	function applicablePages($ids) {
		return $this->applicablePagesHelper($ids, 'canDelete', true, false);
	}
}

/**
 * Unpublish (delete from live site) items batch action.
 * 
 * @package cms
 * @subpackage batchaction
 */
class CMSBatchAction_DeleteFromLive extends CMSBatchAction {
	function getActionTitle() {
		return _t('CMSBatchActions.DELETE_PAGES', 'Delete from published site');
	}
	function getDoingText() {
		return _t('CMSBatchActions.DELETING_PAGES', 'Deleting selected pages from the published site');
	}

	function run(DataObjectSet $pages) {
		$status = array(
			'modified'=>array(),
			'deleted'=>array()
		);
		
		foreach($pages as $page) {
			$id = $page->ID;
			
			// Perform the action
			if($page->canDelete()) $page->doDeleteFromLive();

			// check to see if the record exists on the stage site, if it doesn't remove the tree node
			$stageRecord = Versioned::get_one_by_stage( 'SiteTree', 'Stage', "\"SiteTree\".\"ID\"=$id");
			if($stageRecord) {
				$stageRecord->IsAddedToStage = true;
				$status['modified'][$stageRecord->ID] = array(
					'TreeTitle' => $stageRecord->TreeTitle,
				);
			} else {
				$status['deleted'][$id] = array();
			}

		}

		return Convert::raw2json($status);
	}

	function applicablePages($ids) {
		return $this->applicablePagesHelper($ids, 'canDelete', false, true);
	}
}