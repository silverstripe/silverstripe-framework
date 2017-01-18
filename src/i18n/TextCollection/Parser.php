<?php

namespace SilverStripe\i18n\TextCollection;

use SilverStripe\View\SSTemplateParser;

/**
 * Parser that scans through a template and extracts the parameters to the _t and <%t calls
 */
class Parser extends SSTemplateParser
{
    /**
     * Current entity
     *
     * @var array
     */
    protected $entities = [];

    /**
     * List of all entities
     *
     * @var array
     */
    protected $currentEntity = [];

    /**
     * @param string $string
     */
    public function __construct($string)
    {
        parent::__construct();
        $this->string = $string;
        $this->pos = 0;
        $this->depth = 0;
        $this->regexps = array();
    }

    public function Translate__construct(&$res)
    {
        $this->currentEntity = [null, null];
    }

    public function Translate_Entity(&$res, $sub)
    {
        $this->currentEntity[0] = $sub['text']; // key
    }

    public function Translate_Default(&$res, $sub)
    {
        $this->currentEntity[1] = $sub['String']['text']; // default
    }

    public function Translate__finalise(&$res)
    {
        // Capture entity if, and only if, a default vaule is provided
        if ($this->currentEntity[1]) {
            $this->entities[$this->currentEntity[0]] = $this->currentEntity[1];
        }
    }

    /**
     * Parses a template and returns any translatable entities
     *
     * @param string $template String to parse for translations
     * @return array Map of keys -> values
     */
    public static function getTranslatables($template)
    {
        // Run the parser and throw away the result
        $parser = new Parser($template);
        if (substr($template, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
            $parser->pos = 3;
        }
        $parser->match_TopTemplate();
        return $parser->getEntities();
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }
}
