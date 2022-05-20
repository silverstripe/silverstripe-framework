<?php

namespace SilverStripe\i18n\TextCollection;

use SilverStripe\i18n\i18n;
use SilverStripe\View\SSTemplateParser;

/**
 * Parser that scans through a template and extracts the parameters to the _t and <%t calls
 */
class Parser extends SSTemplateParser
{
    /**
     * List of all entities
     *
     * @var array
     */
    protected $entities = [];

    /**
     * Current entity
     *
     * @var array
     */
    protected $currentEntity = [];

    /**
     * Key of current entity
     *
     * @var string
     */
    protected $currentEntityKey = null;

    /*
     * Show warning if default omitted
     *
     * @var bool
     */
    protected $warnIfEmpty = true;

    /**
     * @param string $string
     * @param bool $warnIfEmpty
     */
    public function __construct($string, $warnIfEmpty = true)
    {
        parent::__construct();
        $this->string = $string;
        $this->pos = 0;
        $this->depth = 0;
        $this->regexps = [];
        $this->warnIfEmpty = $warnIfEmpty;
    }

    public function Translate__construct(&$res)
    {
        $this->currentEntity = [];
        $this->currentEntityKey = null;
    }

    public function Translate_Entity(&$res, $sub)
    {
        // Collapse escaped slashes
        $this->currentEntityKey = str_replace('\\\\', '\\', $sub['text'] ?? ''); // key
    }

    public function Translate_Default(&$res, $sub)
    {
        $this->currentEntity['default'] = $sub['String']['text']; // default
    }

    public function Translate_Context(&$res, $sub)
    {
        $this->currentEntity['comment'] = $sub['String']['text']; //comment
    }

    public function Translate__finalise(&$res)
    {
        // Validate entity
        $entity = $this->currentEntity;
        if (empty($entity['default'])) {
            if ($this->warnIfEmpty) {
                trigger_error("Missing localisation default for key " . $this->currentEntityKey, E_USER_NOTICE);
            }
            return;
        }

        // Detect plural forms
        $plurals = i18n::parse_plurals($entity['default']);
        if ($plurals) {
            unset($entity['default']);
            $entity = array_merge($entity, $plurals);
        }

        // If only default is set, simplify
        if (count($entity ?? []) === 1 && !empty($entity['default'])) {
            $entity = $entity['default'];
        }

        $this->entities[$this->currentEntityKey] = $entity;
    }

    /**
     * Parses a template and returns any translatable entities
     *
     * @param string $template String to parse for translations
     * @param bool $warnIfEmpty Show warnings if default omitted
     * @return array Map of keys -> values
     */
    public static function getTranslatables($template, $warnIfEmpty = true)
    {
        // Run the parser and throw away the result
        $parser = new Parser($template, $warnIfEmpty);
        if (substr($template ?? '', 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
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
