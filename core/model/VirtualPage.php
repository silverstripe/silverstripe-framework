<?php
/**
* Virtual Page creates an instance of a  page, with the same fields that the original page had, but readonly.
* This allows you can have a page in mulitple places in the site structure, with different children without duplicating the content
* Note: This Only duplicates $db fields and not the $has_one etc.. 
* @package cms
*/
class VirtualPage extends Page {
	
	static $icon = array("cms/images/treeicons/page-shortcut-gold","file");
	
	public static $virtualFields;
	
	static $has_one = array(
		"CopyContentFrom" => "SiteTree",	
	);
	
	static $db = array(
		"VersionID" => "Int",
	);
	
	/** 
	 * Generates the array of fields required for the page type.
	 */
	function getVirtualFields() {
		$nonVirtualFields = array(
			"SecurityTypeID",
			"OwnerID",
			"URLSegment",
			"Sort",
			"Status",
			'ShowInMenus',
			// 'Locale'
			'ShowInSearch',
			'Version',
			"Embargo",
			"Expiry",
		);

		$allFields = $this->db();
		if($hasOne = $this->has_one()) foreach($hasOne as $link) $allFields[$link . 'ID'] = "Int";
		foreach($allFields as $field => $type) {
			if(!in_array($field, $nonVirtualFields)) $virtualFields[] = $field;
		}
		
		return $virtualFields;
	}

	function CopyContentFrom() {
		if(empty($this->record['CopyContentFromID'])) return new SiteTree();
		
		if(!isset($this->components['CopyContentFrom'])) {
			$this->components['CopyContentFrom'] = DataObject::get_by_id("SiteTree", 
				$this->record['CopyContentFromID']);

			// Don't let VirtualPages point to other VirtualPages
			if($this->components['CopyContentFrom'] instanceof VirtualPage) {
				$this->components['CopyContentFrom'] = null;
			}
				
			// has_one component semantics incidate than an empty object should be returned
			if(!$this->components['CopyContentFrom']) {
				$this->components['CopyContentFrom'] = new SiteTree();
			}
		}
		
		return $this->components['CopyContentFrom'];
	}
	function setCopyContentFromID($val) {
		if(DataObject::get_by_id('SiteTree', $val) instanceof VirtualPage) $val = 0;
		return $this->setField("CopyContentFromID", $val);
	}
 
	function ContentSource() {
		return $this->CopyContentFrom();
	}
	
	function allowedChildren() {
		if($this->CopyContentFrom()) {
			return $this->CopyContentFrom()->allowedChildren();
		}
	}
	
	public function syncLinkTracking() {
		if($this->CopyContentFromID) {
			$this->HasBrokenLink = !(bool) DataObject::get_by_id('SiteTree', $this->CopyContentFromID);
		} else {
			$this->HasBrokenLink = true;
		}
	}
	
	/**
	 * We can only publish the page if there is a published source page
	 */
	public function canPublish($member = null) {
		return $this->isPublishable() && parent::canPublish($member);
	}
	
	/**
	 * Return true if we can delete this page from the live site, which is different from can
	 * we publish it.
	 */
	public function canDeleteFromLive($member = null) {
		return parent::canPublish($member);
	}
	
	/**
	 * Returns true if is page is publishable by anyone at all
	 * Return false if the source page isn't published yet.
	 * 
	 * Note that isPublishable doesn't affect ete from live, only publish.
	 */
	public function isPublishable() {
		// No source
		if(!$this->CopyContentFrom() || !$this->CopyContentFrom()->ID) {
			return false;
		}
		
		// Unpublished source
		if(!Versioned::get_versionnumber_by_stage('SiteTree', 'Live', $this->CopyContentFromID)) {
			return false;
		}
		
		// Default - publishable
		return true;
	}
		
	/**
	 * Generate the CMS fields from the fields from the original page.
	 */
	function getCMSFields($cms = null) {
		$fields = parent::getCMSFields($cms);
		
		// Setup the linking to the original page.
		$copyContentFromField = new TreeDropdownField(
			"CopyContentFromID", 
			_t('VirtualPage.CHOOSE', "Choose a page to link to"), 
			"SiteTree"
		);
		// filter doesn't let you select children of virtual pages as as source page
		//$copyContentFromField->setFilterFunction(create_function('$item', 'return !($item instanceof VirtualPage);'));
		
		// Setup virtual fields
		if($virtualFields = $this->getVirtualFields()) {
			$roTransformation = new ReadonlyTransformation();
			foreach($virtualFields as $virtualField) {
				if($fields->dataFieldByName($virtualField))
					$fields->replaceField($virtualField, $fields->dataFieldByName($virtualField)->transform($roTransformation));
			}
		}
		
		// Add fields to the tab
		$fields->addFieldToTab("Root.Content.Main", 
			new HeaderField('VirtualPageHeader',_t('VirtualPage.HEADER', "This is a virtual page")), 
			"Title"
		);
		$fields->addFieldToTab("Root.Content.Main", $copyContentFromField, "Title");
		
		// Create links back to the original object in the CMS
		if($this->CopyContentFrom()->ID) {
			$linkToContent = "<a class=\"cmsEditlink\" href=\"admin/show/$this->CopyContentFromID\">" . 
				_t('VirtualPage.EDITCONTENT', 'click here to edit the content') . "</a>";
			$fields->addFieldToTab("Root.Content.Main", 
				$linkToContentLabelField = new LabelField('VirtualPageContentLinkLabel', $linkToContent), 
				"Title"
			);
			$linkToContentLabelField->setAllowHTML(true);
		}
	
		return $fields;
	}
	
	/** 
	 * We have to change it to copy all the content from the original page first.
	 */
	function onBeforeWrite() {
		// On regular write, this will copy from published source.  This happens on every publish
		if($this->extension_instances['Versioned']->migratingVersion
			&& Versioned::current_stage() == 'Live') {
			if($this->CopyContentFromID) {
				$performCopyFrom = true;
			
			$stageSourceVersion = DB::query("SELECT \"Version\" FROM \"SiteTree\" WHERE \"ID\" = $this->CopyContentFromID")->value();
			$liveSourceVersion = DB::query("SELECT \"Version\" FROM \"SiteTree_Live\" WHERE \"ID\" = $this->CopyContentFromID")->value();
			
				// We're going to create a new VP record in SiteTree_versions because the published
				// version might not exist, unless we're publishing the latest version
				if($stageSourceVersion != $liveSourceVersion) {
					$this->extension_instances['Versioned']->migratingVersion = null;
				}
			}

		// On regular write, this will copy from draft source.  This is only executed when the source
		// page changeds
		} else {
			$performCopyFrom = $this->isChanged('CopyContentFromID') && $this->CopyContentFromID != 0;
		}
		
		// On publish, this will copy from published source
 		if($performCopyFrom && $this instanceof VirtualPage) {
			// This flush is needed because the get_one cache doesn't respect site version :-(
			singleton('SiteTree')->flushCache();
			$source = DataObject::get_one("SiteTree",sprintf('"SiteTree"."ID" = %d', $this->CopyContentFromID));
			// Leave the updating of image tracking until after write, in case its a new record
			$this->copyFrom($source, false);
			$this->URLSegment = $source->URLSegment;
		}
		
		parent::onBeforeWrite();
	}
	
	function onAfterWrite() {
		parent::onAfterWrite();

		// Don't do this stuff when we're publishing
		if(!$this->extension_instances['Versioned']->migratingVersion) {
	 		if(
				$this->isChanged('CopyContentFromID')
	 			&& $this->CopyContentFromID != 0 
				&& $this instanceof VirtualPage
			) {
				$this->updateImageTracking();
			}
		}
		
		FormResponse::add("$('Form_EditForm').reloadIfSetTo($this->ID);", $this->ID."_VirtualPage_onAfterWrite");
	}
	
	/**
	 * Ensure we have an up-to-date version of everything.
	 */
	function copyFrom($source, $updateImageTracking = true) {
		if($source) {
			foreach($this->getVirtualFields() as $virtualField) {
				$this->$virtualField = $source->$virtualField;
			}
			
			// We also want to copy ShowInMenus, but only if we're copying the
			// source page for the first time.
			if($this->isChanged('CopyContentFromID')) {
				$this->ShowInMenus = $source->ShowInMenus;
			}
			
			if($updateImageTracking) $this->updateImageTracking();
		}
	}
	
	function updateImageTracking() {
		// Doesn't work on unsaved records
		if(!$this->ID) return;

		// Remove CopyContentFrom() from the cache
		unset($this->components['CopyContentFrom']);
		
		// Update ImageTracking
		$this->ImageTracking()->setByIdList($this->CopyContentFrom()->ImageTracking()->column('ID'));
	}
	
	/**
	 * Allow attributes on the master page to pass
	 * through to the virtual page
	 *
	 * @param string $field 
	 * @return mixed
	 */
	function __get($field) {
		if(parent::hasMethod($funcName = "get$field")) {
			return $this->$funcName();
		} else if(parent::hasField($field)) {
			return $this->getField($field);
		} else {
			return $this->copyContentFrom()->$field;
		}
	}
	
	/**
	 * Pass unrecognized method calls on to the original data object
	 *
	 * @param string $method 
	 * @param string $args 
	 */
	function __call($method, $args) {
		if(parent::hasMethod($method)) {
			return parent::__call($method, $args);
		} else {
			return call_user_func_array(array($this->copyContentFrom(), $method), $args);
		}
	}

	public function hasField($field) {
		return (
			array_key_exists($field, $this->record) 
			|| $this->hasDatabaseField($field) 
			|| array_key_exists($field, $this->db()) // Needed for composite fields
			|| parent::hasMethod("get{$field}")
			|| $this->CopyContentFrom()->hasField($field)
		);
	}	
	/**
	 * Overwrite to also check for method on the original data object
	 *
	 * @param string $method 
	 * @return bool 
	 */
	function hasMethod($method) {
		if(parent::hasMethod($method)) return true;
		return $this->copyContentFrom()->hasMethod($method);
	}
}

/**
 * Controller for the virtual page.
 * @package cms
 */
class VirtualPage_Controller extends Page_Controller {
	
	static $allowed_actions = array(
		'loadcontentall' => 'ADMIN',
	);
	
	/**
	 * Reloads the content if the version is different ;-)
	 */
	function reloadContent() {
		$this->failover->copyFrom($this->failover->CopyContentFrom());
		$this->failover->write();
		return;
	}
	
	function getViewer($action) {
		$originalClass = get_class($this->CopyContentFrom());
		if ($originalClass == 'SiteTree') $name = 'Page_Controller';
		else $name = $originalClass."_Controller";
		$controller = new $name();
		return $controller->getViewer($action);
	}
	
	/**
	 * When the virtualpage is loaded, check to see if the versions are the same
	 * if not, reload the content.
	 * NOTE: Virtual page must have a container object of subclass of sitetree.
	 * We can't load the content without an ID or record to copy it from.
	 */
	function init(){
		if(isset($this->record) && $this->record->ID){
			if($this->record->VersionID != $this->failover->CopyContentFrom()->Version){
				$this->reloadContent();
				$this->VersionID = $this->failover->CopyContentFrom()->VersionID;
			}
		}
		parent::init();
	}

	function loadcontentall() {
		$pages = DataObject::get("VirtualPage");
		foreach($pages as $page) {
			$page->copyFrom($page->CopyContentFrom());
			$page->write();
			$page->publish("Stage", "Live");
			echo "<li>Published $page->URLSegment";
		}
	}
	
	/**
	 * Also check the original object's original controller for the method
	 *
	 * @param string $method 
	 * @return bool 
	 */
	function hasMethod($method) {
		$haveIt = parent::hasMethod($method);
		if (!$haveIt) {	
			$originalClass = get_class($this->CopyContentFrom());
			if ($originalClass == 'SiteTree') $name = 'ContentController';
			else $name = $originalClass."_Controller";
			$controller = new $name($this->dataRecord->copyContentFrom());
			$haveIt = $controller->hasMethod($method);
		}
		return $haveIt;
	}
	
	/**
	 * Pass unrecognized method calls on to the original controller
	 *
	 * @param string $method 
	 * @param string $args 
	 */
	function __call($method, $args) {
		try {
			return parent::__call($method, $args);
		} catch (Exception $e) {
			// Hack... detect exception type. We really should use exception subclasses.
			// if the exception isn't a 'no method' error, rethrow it
			if ($e->getCode() !== 2175) throw $e;
			$original = $this->copyContentFrom();
			$originalClass = get_class($original);
			if ($originalClass == 'SiteTree') $name = 'ContentController';
			else $name = $originalClass."_Controller";
			$controller = new $name($this->dataRecord->copyContentFrom());
			return call_user_func_array(array($controller, $method), $args);
		}
	}
}

?>
