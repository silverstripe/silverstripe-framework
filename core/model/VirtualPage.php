<?php
/**
* Virtual Page creates an instance of a  page, with the same fields that the original page had, but readonly.
* This allows you can have a page in mulitple places in the site structure, with different children without duplicating the content
* Note: This Only duplicates $db fields and not the $has_one etc.. 
* @package cms
*/
class VirtualPage extends Page {

	static $add_action = "Virtual page (another page's content)";
	
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
			'ShowInSearch'
		);

		$allFields = $this->db();
		if($hasOne = $this->has_one()) foreach($hasOne as $link) $allFields[$link . 'ID'] = "Int";
		foreach($allFields as $field => $type) {
			if(!in_array($field, $nonVirtualFields)) $virtualFields[] = $field;
		}
		
		return $virtualFields;
	}

	function ContentSource() {
		return $this->CopyContentFrom();
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
		$copyContentFromField->setFilterFunction(create_function('$item', 'return $item->ClassName != "VirtualPage";'));
		
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
		if($this->CopyContentFromID) {
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
		// Don't do this stuff when we're publishing
		if(!$this->extension_instances['Versioned']->migratingVersion) {
	 		if(
				isset($this->changed['CopyContentFromID']) 
				&& $this->changed['CopyContentFromID'] 
	 			&& $this->CopyContentFromID != 0 
				&& $this instanceof VirtualPage
			) {
				$source = DataObject::get_one("SiteTree",sprintf('`SiteTree`.`ID` = %d', $this->CopyContentFromID));
				$this->copyFrom($source);
				$this->URLSegment = $source->URLSegment . '-' . $this->ID;			
			}
		}
		
		parent::onBeforeWrite();
	}
	/**
	 * Ensure we have an up-to-date version of everything.
	 */
	function copyFrom($source) {
		if($source) {
			foreach($this->getVirtualFields() AS $virtualField)
				$this->$virtualField = $source->$virtualField;
		}
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
}

?>