<?php

namespace SilverStripe\i18n;

use SilverStripe\View\SSTemplateParser;

/**
 * Parser that scans through a template and extracts the parameters to the _t and <%t calls
 */
class i18nTextCollector_Parser extends SSTemplateParser
{

    private static $entities = array();

    private static $currentEntity = array();

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
        self::$currentEntity = array(null, null, null); //start with empty array
    }

    public function Translate_Entity(&$res, $sub)
    {
        self::$currentEntity[0] = $sub['text']; //entity
    }

    public function Translate_Default(&$res, $sub)
    {
        self::$currentEntity[1] = $sub['String']['text']; //value
    }

    public function Translate_Context(&$res, $sub)
    {
        self::$currentEntity[2] = $sub['String']['text']; //comment
    }

    public function Translate__finalise(&$res)
    {
        // set the entity name and the value (default), as well as the context (comment)
        // priority is no longer used, so that is blank
        self::$entities[self::$currentEntity[0]] = array(self::$currentEntity[1], null, self::$currentEntity[2]);
    }

    /**
     * Parses a template and returns any translatable entities
     */
    public static function GetTranslatables($template)
    {
        self::$entities = array();

        // Run the parser and throw away the result
        $parser = new i18nTextCollector_Parser($template);
        if (substr($template, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
            $parser->pos = 3;
        }
        $parser->match_TopTemplate();

        return self::$entities;
    }
}
