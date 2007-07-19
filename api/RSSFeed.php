<?php

class RSSFeed extends ViewableData {
	protected $entries;
	protected $title, $description, $link;
	protected $titleField, $descriptionField, $authorField;
	
	function __construct(DataObjectSet $entries, $link, $title, $description = null, $titleField = "Title", $descriptionField = "Content", $authorField = null) {
		$this->entries = $entries;
		$this->link = $link;
		$this->description = $description;
		$this->title = $title;
		
		$this->titleField = $titleField;
		$this->descriptionField = $descriptionField;
		$this->authorField = $authorField;
	}
	
	static function linkToFeed($url, $title = null) {
		$title = Convert::raw2xml($title);
		Requirements::insertHeadTags("<link rel=\"alternate\" type=\"application/rss+xml\" title=\"$title\" href=\"$url\" />");
	}
	
	function Entries() {
		$output = new DataObjectSet();
		foreach($this->entries as $entry) {
			$output->push(new RSSFeed_Entry($entry, $this->titleField, $this->descriptionField, $this->authorField));
		}
		return $output;
	}
	
	function Title() {
		return $this->title;
	}
	function Link() {
		return Director::absoluteURL($this->link);
	}
	function Description() {
		return $this->description;
	}
	
	function outputToBrowser() {
		header("Content-type: text/xml");
		echo str_replace('&nbsp;', '&#160;', $this->renderWith('RSSFeed'));
	}

}

class RSSFeed_Entry extends ViewableData {
	protected $titleField, $descriptionField, $authorField;
	
	/**
	 * Create a new RSSFeed entry.
	 */
	function __construct($entry, $titleField, $descriptionField, $authorField) {
		$this->failover = $entry;
		$this->titleField = $titleField;
		$this->descriptionField = $descriptionField;
		$this->authorField = $authorField;
	}
	
	function Title() {
		if($this->titleField)
			return $this->failover->obj($this->titleField);
	}
	function Description() {
		if($this->descriptionField)
			return $this->failover->obj($this->descriptionField);
	}
	function Author() {
		if($this->authorField)
			return $this->failover->obj($this->authorField);
	}
	
	function AbsoluteLink() {
		return $this->failover->AbsoluteLink();
	}
}

?>