<?php

use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * RSSFeed class
 *
 * This class is used to create an RSS feed.
 * @todo Improve documentation
 * @package framework
 * @subpackage integration
 */
class RSSFeed extends ViewableData {

	/**
	 * Casting information for this object's methods.
	 * Let's us use $Title.XML in templates
	 */
	private static $casting = array(
		"Title" => "Varchar",
		"Description" => "Varchar",
		"Link" => "Varchar",
	);

	/**
	 * Holds the feed entries
	 *
	 * @var SS_List
	 */
	protected $entries;

	/**
	 * Title of the feed
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Description of the feed
	 *
	 * @var string
	 */
	protected $description;

	/**
	 * Link to the feed
	 *
	 * @var string
	 */
	protected $link;

	/**
	 * Name of the title field of feed entries
	 *
	 * @var string
	 */
	protected $titleField;

	/**
	 * Name of the description field of feed entries
	 *
	 * @var string
	 */
	protected $descriptionField;

	/**
	 * Name of the author field of feed entries
	 *
	 * @var string
	 */
	protected $authorField;

	/**
	 * Last modification of the RSS feed
	 *
	 * @var int Unix timestamp of the last modification
	 */
	protected $lastModified;

	/**
	 * ETag for the RSS feed (used for client-site caching)
	 *
	 * @var string The value for the HTTP ETag header.
	 */
	protected $etag;

	/**
	 * Custom template
	 *
	 * @var string
	 */
	protected $template = null;

	/**
	 * Constructor
	 *
	 * @param SS_List $entries RSS feed entries
	 * @param string $link Link to the feed
	 * @param string $title Title of the feed
	 * @param string $description Description of the field
	 * @param string $titleField Name of the field that should be used for the
	 *                           titles for the feed entries
	 * @param string $descriptionField Name of the field that should be used
	 *                                 for the description for the feed
	 *                                 entries
	 * @param string $authorField Name of the field that should be used for
	 *                            the author for the feed entries
	 * @param int $lastModified Unix timestamp of the latest modification
	 *                          (latest posting)
	 * @param string $etag The ETag is an unique identifier that is changed
	 *                         every time the representation does
	 */
	public function __construct(SS_List $entries, $link, $title,
											$description = null, $titleField = "Title",
											$descriptionField = "Content", $authorField = null,
											$lastModified = null, $etag = null) {
		$this->entries = $entries;
		$this->link = $link;
		$this->description = $description;
		$this->title = $title;

		$this->titleField = $titleField;
		$this->descriptionField = $descriptionField;
		$this->authorField = $authorField;

		$this->lastModified = $lastModified;
		$this->etag = $etag;

		parent::__construct();
	}

	/**
	 * Include an link to the feed
	 *
	 * @param string $url URL of the feed
	 * @param string $title Title to show
	 */
	public static function linkToFeed($url, $title = null) {
		$title = Convert::raw2xml($title);
		Requirements::insertHeadTags(
			'<link rel="alternate" type="application/rss+xml" title="' . $title .
			'" href="' . $url . '" />');
	}

	/**
	 * Get the RSS feed entries
	 *
	 * @return SS_List Returns the {@link RSSFeed_Entry} objects.
	 */
	public function Entries() {
		$output = new ArrayList();

		if(isset($this->entries)) {
			foreach($this->entries as $entry) {
				$output->push(
					RSSFeed_Entry::create($entry, $this->titleField, $this->descriptionField, $this->authorField));
			}
		}
		return $output;
	}

	/**
	 * Get the title of thisfeed
	 *
	 * @return string Returns the title of the feed.
	 */
	public function Title() {
		return $this->title;
	}

	/**
	 * Get the URL of this feed
	 *
	 * @param string $action
	 * @return string Returns the URL of the feed.
	 */
	public function Link($action = null) {
		return Controller::join_links(Director::absoluteURL($this->link), $action);
	}

	/**
	 * Get the description of this feed
	 *
	 * @return string Returns the description of the feed.
	 */
	public function Description() {
		return $this->description;
	}

	/**
	 * Output the feed to the browser.
	 *
	 * TODO: Pass $response object to ->outputToBrowser() to loosen dependence on global state for easier testing/prototyping so dev can inject custom SS_HTTPResponse instance.
	 *
	 * @return DBHTMLText
	 */
	public function outputToBrowser() {
		$prevState = Config::inst()->get('SSViewer', 'source_file_comments');
		Config::inst()->update('SSViewer', 'source_file_comments', false);

		$response = Controller::curr()->getResponse();

		if(is_int($this->lastModified)) {
			HTTP::register_modification_timestamp($this->lastModified);
			$response->addHeader("Last-Modified", gmdate("D, d M Y H:i:s", $this->lastModified) . ' GMT');
		}
		if(!empty($this->etag)) {
			HTTP::register_etag($this->etag);
		}

		if(!headers_sent()) {
			HTTP::add_cache_headers();
			$response->addHeader("Content-Type", "application/rss+xml; charset=utf-8");
		}

		Config::inst()->update('SSViewer', 'source_file_comments', $prevState);

		return $this->renderWith($this->getTemplates());
	}

	/**
	 * Set the name of the template to use. Actual template will be resolved
	 * via the standard template inclusion process.
	 *
	 * @param string
	 */
	public function setTemplate($template) {
		$this->template = $template;
	}

	/**
	 * Returns the name of the template to use.
	 *
	 * @return string
	 */
	public function getTemplate() {
		return $this->template;
	}

	/**
	 * Returns the ordered list of preferred templates for rendering this object.
	 * Will prioritise any custom template first, and then templates based on class hiearchy next.
	 *
	 * @return array
	 */
	public function getTemplates() {
		$templates = SSViewer::get_templates_by_class(get_class($this), '', __CLASS__);
		// Prefer any custom template
		if($this->getTemplate()) {
			array_unshift($templates, $this->getTemplate());
		}
		return $templates;
	}
}

/**
 * RSSFeed_Entry class
 *
 * This class is used for entries of an RSS feed.
 *
 * @see RSSFeed
 * @package framework
 * @subpackage integration
 */
class RSSFeed_Entry extends ViewableData {
	/**
	 * The object that represents the item, it contains all the data.
	 *
	 * @var mixed
	 */
	protected $failover;

	/**
	 * Name of the title field of feed entries
	 *
	 * @var string
	 */
	protected $titleField;

	/**
	 * Name of the description field of feed entries
	 *
	 * @var string
	 */
	protected $descriptionField;

	/**
	 * Name of the author field of feed entries
	 *
	 * @var string
	 */
	protected $authorField;

	/**
	 * Create a new RSSFeed entry.
	 * @param ViewableData $entry
	 * @param string $titleField
	 * @param string $descriptionField
	 * @param string $authorField
	 */
	public function __construct($entry, $titleField, $descriptionField, $authorField) {
		$this->failover = $entry;
		$this->titleField = $titleField;
		$this->descriptionField = $descriptionField;
		$this->authorField = $authorField;

		parent::__construct();
	}

	/**
	 * Get the description of this entry
	 *
	 * @return DBField Returns the description of the entry.
	 */
	public function Title() {
		return $this->rssField($this->titleField);
	}

	/**
	 * Get the description of this entry
	 *
	 * @return DBField Returns the description of the entry.
	 */
	public function Description() {
		$description = $this->rssField($this->descriptionField);

		// HTML fields need links re-written
		if($description instanceof DBHTMLText) {
			return $description->obj('AbsoluteLinks');
		}

		return $description;
	}

	/**
	 * Get the author of this entry
	 *
	 * @return DBField Returns the author of the entry.
	 */
	public function Author() {
		return $this->rssField($this->authorField);
	}

	/**
	 * Return the safely casted field
	 *
	 * @param string $fieldName Name of field
	 * @return DBField
	 */
	public function rssField($fieldName) {
		if($fieldName) {
			return $this->failover->obj($fieldName);
		}
		return null;
	}

	/**
	 * Get a link to this entry
	 *
	 * @return string Returns the URL of this entry
	 * @throws BadMethodCallException
	 */
	public function AbsoluteLink() {
		if($this->failover->hasMethod('AbsoluteLink')) {
			return $this->failover->AbsoluteLink();
		} else if($this->failover->hasMethod('Link')) {
			return Director::absoluteURL($this->failover->Link());
		}

		throw new BadMethodCallException(
			$this->failover->class .
			" object has neither an AbsoluteLink nor a Link method." .
			" Can't put a link in the RSS feed", E_USER_WARNING
		);
	}
}
