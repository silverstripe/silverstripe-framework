<?php

namespace SilverStripe\Control\RSS;

use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTP;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;

/**
 * RSSFeed class
 *
 * This class is used to create an RSS feed.
 */
class RSSFeed extends ViewableData
{

    /**
     * Casting information for this object's methods.
     * Let's us use $Title.XML in templates
     */
    private static $casting = [
        "Title" => "Varchar",
        "Description" => "Varchar",
        "Link" => "Varchar",
    ];

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
    public function __construct(
        SS_List $entries,
        $link,
        $title,
        $description = null,
        $titleField = "Title",
        $descriptionField = "Content",
        $authorField = null,
        $lastModified = null,
        $etag = null
    ) {
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
    public static function linkToFeed($url, $title = null)
    {
        $title = Convert::raw2xml($title);
        Requirements::insertHeadTags(
            '<link rel="alternate" type="application/rss+xml" title="' . $title . '" href="' . $url . '" />'
        );
    }

    /**
     * Get the RSS feed entries
     *
     * @return SS_List Returns the {@link RSSFeed_Entry} objects.
     */
    public function Entries()
    {
        $output = new ArrayList();

        if (isset($this->entries)) {
            foreach ($this->entries as $entry) {
                $output->push(
                    RSSFeed_Entry::create($entry, $this->titleField, $this->descriptionField, $this->authorField)
                );
            }
        }
        return $output;
    }

    /**
     * Get the title of thisfeed
     *
     * @return string Returns the title of the feed.
     */
    public function Title()
    {
        return $this->title;
    }

    /**
     * Get the URL of this feed
     *
     * @param string $action
     * @return string Returns the URL of the feed.
     */
    public function Link($action = null)
    {
        return Controller::join_links(Director::absoluteURL((string) $this->link), $action);
    }

    /**
     * Get the description of this feed
     *
     * @return string Returns the description of the feed.
     */
    public function Description()
    {
        return $this->description;
    }

    /**
     * Output the feed to the browser.
     *
     * @return DBHTMLText
     */
    public function outputToBrowser()
    {
        $prevState = SSViewer::config()->uninherited('source_file_comments');
        SSViewer::config()->set('source_file_comments', false);

        $response = Controller::curr()->getResponse();

        if (is_int($this->lastModified)) {
            HTTPCacheControlMiddleware::singleton()->registerModificationDate($this->lastModified);
            $response->addHeader("Last-Modified", gmdate("D, d M Y H:i:s", $this->lastModified) . ' GMT');
        }
        if (!empty($this->etag)) {
            $response->addHeader('ETag', "\"{$this->etag}\"");
        }

        $response->addHeader("Content-Type", "application/rss+xml; charset=utf-8");

        SSViewer::config()->set('source_file_comments', $prevState);
        return $this->renderWith($this->getTemplates());
    }

    /**
     * Set the name of the template to use. Actual template will be resolved
     * via the standard template inclusion process.
     *
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Returns the name of the template to use.
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Returns the ordered list of preferred templates for rendering this object.
     * Will prioritise any custom template first, and then templates based on class hierarchy next.
     *
     * @return array
     */
    public function getTemplates()
    {
        $templates = SSViewer::get_templates_by_class(static::class, '', __CLASS__);
        // Prefer any custom template
        if ($this->getTemplate()) {
            array_unshift($templates, $this->getTemplate());
        }
        return $templates;
    }
}
