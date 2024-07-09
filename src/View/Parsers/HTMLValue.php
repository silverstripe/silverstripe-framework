<?php

namespace SilverStripe\View\Parsers;

use SilverStripe\Core\Convert;
use SilverStripe\View\ViewableData;
use Masterminds\HTML5;
use DOMNodeList;
use DOMXPath;
use DOMDocument;
use SilverStripe\View\HTML;

/**
 * This class handles the converting of HTML fragments between a string and a DOMDocument based
 * representation.
 *
 * @mixin DOMDocument
 */
class HTMLValue extends ViewableData
{
    public function __construct($fragment = null)
    {
        if ($fragment) {
            $this->setContent($fragment);
        }
        parent::__construct();
    }

    /**
     * @param string $content
     * @return bool
     */
    public function setContent($content)
    {
        $content = preg_replace('#</?(html|head(?!er)|body)[^>]*>#si', '', $content);
        $html5 = new HTML5(['disable_html_ns' => true]);
        $document = $html5->loadHTML(
            '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head>' .
            "<body>$content</body></html>"
        );
        if ($document) {
            $this->setDocument($document);
            return true;
        }
        $this->valid = false;
        return false;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $document = $this->getDocument();
        if (!$document) {
            return '';
        }
        $doc = clone $document;
        $xp = new DOMXPath($doc);

        // If there's no body, the content is empty string
        if (!$doc->getElementsByTagName('body')->length) {
            return '';
        }

        // saveHTML Percentage-encodes any URI-based attributes. We don't want this, since it interferes with
        // shortcodes. So first, save all the attribute values for later restoration.
        $attrs = [];
        $i = 0;

        foreach ($xp->query('//body//@*') as $attr) {
            $key = "__HTMLVALUE_" . ($i++);
            $attrs[$key] = $attr->value;
            $attr->value = $key;
        }

        // Then, call saveHTML & extract out the content from the body tag
        $res = preg_replace(
            [
                '/^(.*?)<body>/is',
                '/<\/body>(.*?)$/isD',
            ],
            '',
            $doc->saveHTML() ?? ''
        );

        // Then replace the saved attributes with their original versions
        $res = preg_replace_callback('/__HTMLVALUE_(\d+)/', function ($matches) use ($attrs) {
            return Convert::raw2att($attrs[$matches[0]]);
        }, $res ?? '');

        // Prevent &nbsp; being encoded as literal utf-8 characters
        // Possible alternative solution: http://stackoverflow.com/questions/2142120/php-encoding-with-domdocument
        $from = mb_convert_encoding('&nbsp;', 'utf-8', 'html-entities');
        $res = str_replace($from ?? '', '&nbsp;', $res ?? '');

        return $res;
    }

    /** @see HTMLValue::getContent() */
    public function forTemplate()
    {
        return $this->getContent();
    }

    /** @var DOMDocument */
    private $document = null;
    /** @var bool */
    private $valid = true;

    /**
     * Get the DOMDocument for the passed content
     * @return DOMDocument | false - Return false if HTML not valid, the DOMDocument instance otherwise
     */
    public function getDocument()
    {
        if (!$this->valid) {
            return false;
        } elseif ($this->document) {
            return $this->document;
        } else {
            $this->document = new DOMDocument('1.0', 'UTF-8');
            $this->document->strictErrorChecking = false;
            $this->document->formatOutput = false;

            return $this->document;
        }
    }

    /**
     * Is this HTMLValue in an errored state?
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param DOMDocument $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
        $this->valid = true;
    }

    public function setInvalid()
    {
        $this->document = $this->valid = false;
    }

    /**
     * Pass through any missed method calls to DOMDocument (if they exist)
     * so that HTMLValue can be treated mostly like an instance of DOMDocument
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $doc = $this->getDocument();

        if ($doc && method_exists($doc, $method ?? '')) {
            return call_user_func_array([$doc, $method], $arguments ?? []);
        } else {
            return parent::__call($method, $arguments);
        }
    }

    /**
     * Get the body element, or false if there isn't one (we haven't loaded any content
     * or this instance is in an invalid state)
     */
    public function getBody()
    {
        $doc = $this->getDocument();
        if (!$doc) {
            return false;
        }

        $body = $doc->getElementsByTagName('body');
        if (!$body->length) {
            return false;
        }

        return $body->item(0);
    }

    /**
     * Make an xpath query against this HTML
     *
     * @param string $query The xpath query string
     * @return DOMNodeList
     */
    public function query($query)
    {
        $xp = new DOMXPath($this->getDocument());
        return $xp->query($query);
    }
}
