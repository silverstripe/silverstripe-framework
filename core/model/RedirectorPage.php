<?php

/**
 * A redirector page redirects when the page is visited.
 */
class RedirectorPage extends Page {
	static $add_action = "a redirector to another page";
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
			return $this->ExternalURL;
		} else {
			$linkTo = $this->LinkToID ? DataObject::get_by_id("SiteTree", $this->LinkToID) : null;
			if($linkTo) {
				return $linkTo->Link();
			}
		}
	}
	
	function getCMSFields() {
		Requirements::javascript("sapphire/javascript/RedirectorPage.js");
		
    	return new FieldSet(
			new TabSet("Root",
				new Tab("Content",
					new TextField("Title", "Page name"),
					new TextField("MenuTitle", "Navigation label"),
					new FieldGroup("URL",
						new LabelField("http://www.yoursite.com/"),
						new TextField("URLSegment",""),
						new LabelField("/")
					),
					new HeaderField("This page will redirect users to another page"),
					new OptionsetField("RedirectionType", "Redirect to", array(
						"Internal" => "A page on your website",
						"External" => "Another website",
					), "Internal"),
					new TreeDropdownField("LinkToID", "Page on your website", "SiteTree"),
					new TextField("ExternalURL", "Other websiteURL"),
					new TextareaField("MetaDescription", "Meta Description")
				),
				new Tab("Behaviour",
					new DropdownField("ClassName", "Page type", $this->getClassDropdown()),
					new CheckboxField("ShowInMenus", "Show in menus?"),
					new CheckboxField("ShowInSearch", "Show in search?")
				)
			)
		);
	}
}

class RedirectorPage_Controller extends Page_Controller {
	function init() {
		if($this->RedirectionType == 'External') {
			if($this->ExternalURL) Director::redirect($this->ExternalURL);
			else echo "<p>A redirector page has been set up without anywhere to redirect to.</p>";
		} else {
			$linkTo = DataObject::get_by_id("SiteTree", $this->LinkToID);
			if($linkTo) Director::redirect($linkTo->Link());
			else echo "<p>A redirector page has been set up without anywhere to redirect to.</p>";
		}
		
		parent::init();
	}
}
?>
