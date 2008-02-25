<?php

/**
 * @package cms
 * @subpackage content
 */

/**
 * A redirector page redirects when the page is visited.
 * @package cms
 * @subpackage content
 */
class RedirectorPage extends Page {
	static $add_action = "Redirector to another page";
	static $icon = array("cms/images/treeicons/page-shortcut","file");
	
	static $db = array(
		"RedirectionType" => "Enum('Internal,External','Internal')",
		"ExternalURL" => "Varchar(255)",
	);
	
	static $has_one = array(
		"LinkTo" => "SiteTree",
	);
	
	static $many_many = array(
	);
	
	/**
	 * Returns this page if the redirect is external, otherwise
	 * returns the target page.
	 * @return SiteTree
	 */
	function ContentSource() {
		if($this->RedirectionType == 'Internal') {
			return $this->LinkTo();
		} else {
			return $this;
		}		
	}
	
	function Link() {
		if($this->RedirectionType == 'External') {
			return Convert::raw2att($this->ExternalURL);
		} else {
			$linkTo = $this->LinkToID ? DataObject::get_by_id("SiteTree", $this->LinkToID) : null;
			if($linkTo) {
				return $linkTo->Link();
			}
		}
	}
	
	function getCMSFields() {
		Requirements::javascript("sapphire/javascript/RedirectorPage.js");
		
    	$fields = new FieldSet(
			new TabSet("Root",
				$tabContent = new Tab("Content",
					new TextField("Title", _t('SiteTree.PAGETITLE')),
					new TextField("MenuTitle", _t('SiteTree.MENUTITLE')),
					new FieldGroup(_t('SiteTree.URL'),
						new LabelField("http://www.yoursite.com/"),
						new TextField("URLSegment",""),
						new LabelField("/")
					),
					new HeaderField(_t('RedirectorPage.HEADER', "This page will redirect users to another page")),
					new OptionsetField(
						"RedirectionType", 
						_t('RedirectorPage.REDIRECTTO', "Redirect to"), 
						array(
							"Internal" => _t('RedirectorPage.REDIRECTTOPAGE', "A page on your website"),
							"External" => _t('RedirectorPage.REDIRECTTOEXTERNAL', "Another website"),
						), 
						"Internal"
					),
					new TreeDropdownField(	
						"LinkToID", 
						_t('RedirectorPage.YOURPAGE', "Page on your website"), 
						"SiteTree"
					),
					new TextField("ExternalURL", _t('RedirectorPage.OTHERURL', "Other website URL")),
					new TextareaField("MetaDescription", _t('SiteTree.METADESC'))
				),
				$tabBehaviour = new Tab("Behaviour",
					new DropdownField("ClassName", _t('SiteTree.PAGETYPE'), $this->getClassDropdown()),
					new CheckboxField("ShowInMenus", _t('SiteTree.SHOWINMENUS')),
					new CheckboxField("ShowInSearch", _t('SiteTree.SHOWINSEARCH'))
				)
			)
		);
		
		$tabContent->setTitle(_t('SiteTree.TABCONTENT'));
		$tabBehaviour->setTitle(_t('SiteTree.TABBEHAVIOUR'));
		
		return $fields;
	}
}

/**
 * Controller for the {@link RedirectorPage}.
 * @package cms
 * @subpackage content
 */
class RedirectorPage_Controller extends Page_Controller {
	function init() {
		if($this->RedirectionType == 'External') {
			if($this->ExternalURL) {
				Director::redirect($this->ExternalURL);
			} else {
				echo "<p>" .
					_t('RedirectorPage.HASBEENSETUP', 'A redirector page has been set up without anywhere to redirect to.') .
					"</p>";
			}
		} else {
			$linkTo = DataObject::get_by_id("SiteTree", $this->LinkToID);
			if($linkTo) {
				Director::redirect($linkTo->Link());
			} else {
				echo "<p>" . _t('RedirectorPage.HASBEENSETUP') . "</p>";
			}
		}
		
		parent::init();
	}
}
?>