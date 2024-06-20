<?php

namespace SilverStripe\View\Parsers;

use DOMNodeList;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use InvalidArgumentException;
use DOMElement;

/**
 * A simple parser that allows you to map BBCode-like "shortcodes" to an arbitrary callback.
 * It is a simple regex based parser that allows you to replace simple bbcode-like tags
 * within a DBHTMLText or DBHTMLVarchar field when rendered into a template. The API is inspired by and very similar to the
 * [Wordpress implementation](http://codex.wordpress.org/Shortcode_API) of shortcodes.
 *
 * @see http://doc.silverstripe.org/framework/en/reference/shortcodes
 */
class ShortcodeParser
{
    use Injectable;
    use Configurable;
    use Extensible;

    public function __construct()
    {
    }

    public function img_shortcode($attrs)
    {
        return "<img src='" . $attrs['src'] . "'>";
    }

    protected static $instances = [];

    protected static $active_instance = 'default';

    // --------------------------------------------------------------------------------------------------------------

    /**
     * Registered shortcodes. Items follow this structure:
     * [shortcode_name] => Array(
     *     [0] => class_containing_handler
     *     [1] => name_of_shortcode_handler_method
     * )
     */
    protected $shortcodes = [];

    // --------------------------------------------------------------------------------------------------------------

    /**
     * Get the {@link ShortcodeParser} instance that is attached to a particular identifier.
     *
     * @param string $identifier Defaults to "default".
     * @return ShortcodeParser
     */
    public static function get($identifier = 'default')
    {
        if (!array_key_exists($identifier, ShortcodeParser::$instances)) {
            ShortcodeParser::$instances[$identifier] = static::create();
        }

        return ShortcodeParser::$instances[$identifier];
    }

    /**
     * Get the currently active/default {@link ShortcodeParser} instance.
     *
     * @return ShortcodeParser
     */
    public static function get_active()
    {
        return static::get(ShortcodeParser::$active_instance);
    }

    /**
     * Set the identifier to use for the current active/default {@link ShortcodeParser} instance.
     *
     * @param string $identifier
     */
    public static function set_active($identifier)
    {
        ShortcodeParser::$active_instance = (string) $identifier;
    }

    // --------------------------------------------------------------------------------------------------------------

    /**
     * Register a shortcode, and attach it to a PHP callback.
     *
     * The callback for a shortcode will have the following arguments passed to it:
     *   - Any parameters attached to the shortcode as an associative array (keys are lower-case).
     *   - Any content enclosed within the shortcode (if it is an enclosing shortcode). Note that any content within
     *     this will not have been parsed, and can optionally be fed back into the parser.
     *   - The {@link ShortcodeParser} instance used to parse the content.
     *   - The shortcode tag name that was matched within the parsed content.
     *   - An associative array of extra information about the shortcode being parsed.
     *
     * @param string $shortcode The shortcode tag to map to the callback - normally in lowercase_underscore format.
     * @param callable $callback The callback to replace the shortcode with.
     * @return $this
     */
    public function register($shortcode, $callback)
    {
        if (is_callable($callback)) {
            $this->shortcodes[$shortcode] = $callback;
        } else {
            throw new InvalidArgumentException("Callback is not callable");
        }
        return $this;
    }

    /**
     * Check if a shortcode has been registered.
     *
     * @param string $shortcode
     * @return bool
     */
    public function registered($shortcode)
    {
        return array_key_exists($shortcode, $this->shortcodes ?? []);
    }

    /**
     * Remove a specific registered shortcode.
     *
     * @param string $shortcode
     */
    public function unregister($shortcode)
    {
        if ($this->registered($shortcode)) {
            unset($this->shortcodes[$shortcode]);
        }
    }

    /**
     * Get an array containing information about registered shortcodes
     *
     * @return array
     */
    public function getRegisteredShortcodes()
    {
        return $this->shortcodes;
    }

    /**
     * Remove all registered shortcodes.
     */
    public function clear()
    {
        $this->shortcodes = [];
    }

    /**
     * Call a shortcode and return its replacement text
     * Returns false if the shortcode isn't registered
     *
     * @param string $tag
     * @param array $attributes
     * @param string $content
     * @param array $extra
     * @return mixed
     */
    public function callShortcode($tag, $attributes, $content, $extra = [])
    {
        if (!$tag || !isset($this->shortcodes[$tag])) {
            return false;
        }
        return call_user_func($this->shortcodes[$tag], $attributes, $content, $this, $tag, $extra);
    }

    /**
     * Return the text to insert in place of a shoprtcode.
     * Behaviour in the case of missing shortcodes depends on the setting of ShortcodeParser::$error_behavior.
     *
     * @param array $tag A map containing the the following keys:
     *  - 'open': The name of the tag
     *  - 'attrs': Attributes of the tag
     *  - 'content': Content of the tag
     * @param array $extra Extra-meta data
     * @param boolean $isHTMLAllowed A boolean indicating whether it's okay to insert HTML tags into the result
     *
     * @return bool|mixed|string
     */
    public function getShortcodeReplacementText($tag, $extra = [], $isHTMLAllowed = true)
    {
        $content = $this->callShortcode($tag['open'], $tag['attrs'], $tag['content'], $extra);

        // Missing tag
        if ($content === false) {
            if (ShortcodeParser::$error_behavior == ShortcodeParser::ERROR) {
                throw new \InvalidArgumentException('Unknown shortcode tag ' . $tag['open']);
            } elseif (ShortcodeParser::$error_behavior == ShortcodeParser::WARN && $isHTMLAllowed) {
                $content = '<strong class="warning">' . $tag['text'] . '</strong>';
            } elseif (ShortcodeParser::$error_behavior == ShortcodeParser::STRIP) {
                return '';
            } else {
                return $tag['text'];
            }
        }

        return $content;
    }

    // --------------------------------------------------------------------------------------------------------------

    protected function removeNode($node)
    {
        $node->parentNode->removeChild($node);
    }

    /**
     * @param DOMElement $new
     * @param DOMElement $after
     */
    protected function insertAfter($new, $after)
    {
        $parent = $after->parentNode;
        $next = $after->nextSibling;

        if ($next) {
            $parent->insertBefore($new, $next);
        } elseif ($parent) {
            $parent->appendChild($new);
        }
    }

    /**
     * @param DOMNodeList $new
     * @param DOMElement $after
     */
    protected function insertListAfter($new, $after)
    {
        $doc = $after->ownerDocument;
        $parent = $after->parentNode;
        $next = $after->nextSibling;

        for ($i = 0; $i < $new->length; $i++) {
            $imported = $doc->importNode($new->item($i), true);

            if ($next) {
                $parent->insertBefore($imported, $next);
            } else {
                $parent->appendChild($imported);
            }
        }
    }

    /**
     * @var string
     */
    protected static $marker_class = '--ss-shortcode-marker';

    protected static $block_level_elements = [
        'address', 'article', 'aside', 'audio', 'blockquote', 'canvas', 'dd', 'div', 'dl', 'fieldset', 'figcaption',
        'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'ol', 'output', 'p',
        'pre', 'section', 'table', 'ul'
    ];

    protected static $attrrx = '
		([^\s\/\'"=,]+)       # Name
		\s* = \s*
		(?:
			(?:\'([^\']*)\') | # Value surrounded by \'
			(?:"([^"]*)")    | # Value surrounded by "
			([^\s,\]]+)          # Bare value
		)
';

    protected static function attrrx()
    {
        return '/' . ShortcodeParser::$attrrx . '/xS';
    }

    protected static $tagrx = '
		# HTML Tag
		<(?<element>(?:"[^"]*"[\'"]*|\'[^\']*\'[\'"]*|[^\'">])+)>

		| # Opening tag
		(?<oesc>\[?)
		\[
			(?<open>\w+)
			[\s,]*
			(?<attrs> (?: %s [\s,]*)* )
		\/?\]
		(?<cesc1>\]?)

		| # Closing tag
		\[\/
			(?<close>\w+)
		\]
		(?<cesc2>\]?)
';

    protected static function tagrx()
    {
        return '/' . sprintf(ShortcodeParser::$tagrx, ShortcodeParser::$attrrx) . '/xS';
    }

    const WARN = 'warn';
    const STRIP = 'strip';
    const LEAVE = 'leave';
    const ERROR = 'error';

    public static $error_behavior = ShortcodeParser::LEAVE;


    /**
     * Look through a string that contains shortcode tags and pull out the locations and details
     * of those tags
     *
     * Doesn't support nested shortcode tags
     *
     * @param string $content
     * @return array - The list of tags found. When using an open/close pair, only one item will be in the array,
     * with "content" set to the text between the tags
     */
    public function extractTags($content)
    {
        $tags = [];

        // Step 1: perform basic regex scan of individual tags
        if (preg_match_all(static::tagrx() ?? '', $content ?? '', $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                // Ignore any elements
                if (empty($match['open'][0]) && empty($match['close'][0])) {
                    continue;
                }

                // Pull the attributes out into a key/value hash
                $attrs = [];

                if (!empty($match['attrs'][0])) {
                    preg_match_all(static::attrrx() ?? '', $match['attrs'][0] ?? '', $attrmatches, PREG_SET_ORDER);

                    foreach ($attrmatches as $attr) {
                        $name = '';
                        $value = '';
                        $parts = array_values(array_filter($attr ?? []));
                        //the first element in the array is the complete delcaration (`id=1`) - we don't need this
                        array_shift($parts);

                        //the next two parts are what we care about (id and 1 from `id=1`)
                        $name = array_shift($parts) ?: $name;
                        $value = array_shift($parts) ?: $value;

                        $attrs[$name] = $value;
                    }
                }

                // And store the indexes, tag details, etc
                $tags[] = [
                    'text' => $match[0][0],
                    's' => $match[0][1],
                    'e' => $match[0][1] + strlen($match[0][0] ?? ''),
                    'open' =>  isset($match['open'][0]) ? $match['open'][0] : null,
                    'close' => isset($match['close'][0]) ? $match['close'][0] : null,
                    'attrs' => $attrs,
                    'content' => '',
                    'escaped' => !empty($match['oesc'][0]) || !empty($match['cesc1'][0]) || !empty($match['cesc2'][0])
                ];
            }
        }

        // Step 2: cluster open/close tag pairs into single entries
        $i = count($tags ?? []);
        while ($i--) {
            if (!empty($tags[$i]['close'])) {
                // If the tag just before this one isn't the related opening tag, throw an error
                $err = null;

                if ($i == 0) {
                    $err = 'Close tag "' . $tags[$i]['close'] . '" is the first found tag, so has no related open tag';
                } elseif (!$tags[$i-1]['open']) {
                    $err = 'Close tag "' . $tags[$i]['close'] . '" preceded by another close tag "' . $tags[$i-1]['close'] . '"';
                } elseif ($tags[$i]['close'] != $tags[$i-1]['open']) {
                    $err = 'Close tag "' . $tags[$i]['close'] . '" doesn\'t match preceding open tag "' . $tags[$i-1]['open'] . '"';
                }

                if ($err) {
                    if (ShortcodeParser::$error_behavior == ShortcodeParser::ERROR) {
                        throw new \Exception($err);
                    }
                } else {
                    if ($tags[$i]['escaped']) {
                        if (!$tags[$i-1]['escaped']) {
                            $tags[$i]['e'] -= 1;
                            $tags[$i]['escaped'] = false;
                        }
                    } else {
                        if ($tags[$i-1]['escaped']) {
                            $tags[$i-1]['s'] += 1;
                            $tags[$i-1]['escaped'] = false;
                        }
                    }

                    // Otherwise, grab content between tags, save in opening tag & delete the closing one
                    $tags[$i-1]['text'] = substr($content ?? '', $tags[$i-1]['s'] ?? 0, $tags[$i]['e'] - $tags[$i-1]['s']);
                    $tags[$i-1]['content'] = substr($content ?? '', $tags[$i-1]['e'] ?? 0, $tags[$i]['s'] - $tags[$i-1]['e']);
                    $tags[$i-1]['e'] = $tags[$i]['e'];

                    unset($tags[$i]);
                }
            }
        }

        // Step 3: remove any tags that don't have handlers registered
        // Only do this if ShortcodeParser::$error_behavior == ShortcodeParser::LEAVE
        // This is optional but speeds things up.
        if (ShortcodeParser::$error_behavior == ShortcodeParser::LEAVE) {
            foreach ($tags as $i => $tag) {
                if (empty($this->shortcodes[$tag['open']])) {
                    unset($tags[$i]);
                }
            }
        }

        return array_values($tags ?? []);
    }

    /**
     * Replaces the shortcode tags extracted by extractTags with HTML element "markers", so that
     * we can parse the resulting string as HTML and easily mutate the shortcodes in the DOM
     *
     * @param string $content The HTML string with [tag] style shortcodes embedded
     * @param array $tags The tags extracted by extractTags
     * @param callable $generator
     * @return string The HTML string with [tag] style shortcodes replaced by markers
     */
    protected function replaceTagsWithText($content, $tags, $generator)
    {
        // The string with tags replaced with markers
        $str = '';
        // The start index of the next tag, remembered as we step backwards through the list
        $li = null;

        $i = count($tags ?? []);
        while ($i--) {
            if ($li === null) {
                $tail = substr($content ?? '', $tags[$i]['e'] ?? 0);
            } else {
                $tail = substr($content ?? '', $tags[$i]['e'] ?? 0, $li - $tags[$i]['e']);
            }

            if ($tags[$i]['escaped']) {
                $str = substr($content ?? '', $tags[$i]['s']+1, $tags[$i]['e'] - $tags[$i]['s'] - 2) . $tail . $str;
            } else {
                $str = $generator($i, $tags[$i]) . $tail . $str;
            }

            $li = $tags[$i]['s'];
        }

        return substr($content ?? '', 0, $tags[0]['s']) . $str;
    }

    /**
     * Replace the shortcodes in attribute values with the calculated content
     *
     * We don't use markers with attributes because there's no point, it's easier to do all the matching
     * in-DOM after the XML parse
     *
     * @param HTMLValue $htmlvalue
     */
    protected function replaceAttributeTagsWithContent($htmlvalue)
    {
        $attributes = $htmlvalue->query('//@*[contains(.,"[")][contains(.,"]")]');
        $parser = $this;

        for ($i = 0; $i < $attributes->length; $i++) {
            $node = $attributes->item($i);
            $tags = $this->extractTags($node->nodeValue);
            $extra = ['node' => $node, 'element' => $node->ownerElement];

            if ($tags) {
                $node->nodeValue = $this->replaceTagsWithText(
                    htmlspecialchars($node->nodeValue ?? ''),
                    $tags,
                    function ($idx, $tag) use ($parser, $extra) {
                        return $parser->getShortcodeReplacementText($tag, $extra, false);
                    }
                );
            }
        }
    }

    /**
     * Replace the element-scoped tags with markers
     *
     * @param string $content
     * @return array
     */
    protected function replaceElementTagsWithMarkers($content)
    {
        $tags = $this->extractTags($content);

        if ($tags) {
            $markerClass = ShortcodeParser::$marker_class;

            $content = $this->replaceTagsWithText($content, $tags, function ($idx, $tag) use ($markerClass) {
                return '<img class="' . $markerClass . '" data-tagid="' . $idx . '" />';
            });
        }

        return [$content, $tags];
    }

    /**
     * @param DOMNodeList $nodes
     * @return array
     */
    protected function findParentsForMarkers($nodes)
    {
        $parents = [];

        /** @var DOMElement $node */
        foreach ($nodes as $node) {
            $parent = $node;

            do {
                $parent = $parent->parentNode;
            } while ($parent instanceof DOMElement &&
                !in_array(strtolower($parent->tagName ?? ''), ShortcodeParser::$block_level_elements)
            );

            $node->setAttribute('data-parentid', count($parents ?? []));
            $parents[] = $parent;
        }

        return $parents;
    }

    const BEFORE = 'before';
    const AFTER = 'after';
    const SPLIT = 'split';
    const INLINE = 'inline';

    /**
     * Given a node with represents a shortcode marker and a location string, mutates the DOM to put the
     * marker in the compliant location
     *
     * For shortcodes inserted BEFORE, that location is just before the block container that
     * the marker is in
     *
     * For shortcodes inserted AFTER, that location is just after the block container that
     * the marker is in
     *
     * For shortcodes inserted SPLIT, that location is where the marker is, but the DOM
     * is split around it up to the block container the marker is in - for instance,
     *
     *   <p>A<span>B<marker />C</span>D</p>
     *
     * becomes
     *
     *   <p>A<span>B</span></p><marker /><p><span>C</span>D</p>
     *
     * For shortcodes inserted INLINE, no modification is needed (but in that case the shortcode handler needs to
     * generate only inline blocks)
     *
     * @param DOMElement $node
     * @param DOMElement $parent
     * @param int $location ShortcodeParser::BEFORE, ShortcodeParser::SPLIT or ShortcodeParser::INLINE
     */
    protected function moveMarkerToCompliantHome($node, $parent, $location)
    {
        // Move before block parent
        if ($location == ShortcodeParser::BEFORE) {
            if (isset($parent->parentNode)) {
                $parent->parentNode->insertBefore($node, $parent);
            }
        } elseif ($location == ShortcodeParser::AFTER) {
            // Move after block parent
            $this->insertAfter($node, $parent);
        } elseif ($location == ShortcodeParser::SPLIT) {
            // Split parent at node
            $at = $node;
            $splitee = $node->parentNode;

            while ($splitee !== $parent->parentNode) {
                /** @var DOMElement $spliter */
                $spliter = $splitee->cloneNode(false);

                $this->insertAfter($spliter, $splitee);

                while ($at->nextSibling) {
                    $spliter->appendChild($at->nextSibling);
                }

                $at = $splitee;
                $splitee = $splitee->parentNode;
            }

            $this->insertAfter($node, $parent);
        } elseif ($location == ShortcodeParser::INLINE) {
            // Do nothing
            if (in_array(strtolower($node->tagName ?? ''), ShortcodeParser::$block_level_elements)) {
                user_error(
                    'Requested to insert block tag ' . $node->tagName . ' inline - probably this will break HTML compliance',
                    E_USER_WARNING
                );
            }
            // NOP
        } else {
            throw new \UnexpectedValueException('Unknown value for $location argument ' . $location);
        }
    }

    /**
     * Given a node with represents a shortcode marker and some information about the shortcode, call the
     * shortcode handler & replace the marker with the actual content
     *
     * @param DOMElement $node
     * @param array $tag
     */
    protected function replaceMarkerWithContent($node, $tag)
    {
        $content = $this->getShortcodeReplacementText($tag);

        if ($content) {
            $parsed = HTMLValue::create($content);
            $body = $parsed->getBody();
            if ($body) {
                $this->insertListAfter($body->childNodes, $node);
            }
        }

        $this->removeNode($node);
    }

    /**
     * Parse a string, and replace any registered shortcodes within it with the result of the mapped callback.
     *
     * @param string $content
     * @return string
     */
    public function parse($content)
    {
        $this->extend('onBeforeParse', $content);

        $continue = true;

        // If no shortcodes defined, don't try and parse any
        if (!$this->shortcodes) {
            $continue = false;
        } elseif (!trim($content ?? '')) {
            // If no content, don't try and parse it
            $continue = false;
        } elseif (strpos($content ?? '', '[') === false) {
            // If no shortcode tag, don't try and parse it
            $continue = false;
        }

        if ($continue) {
            // First we operate in text mode, replacing any shortcodes with marker elements so that later we can
            // use a proper DOM
            list($content, $tags) = $this->replaceElementTagsWithMarkers($content);

            $htmlvalue = Injector::inst()->create(HTMLValue::class, $content);

            // Now parse the result into a DOM
            if (!$htmlvalue->isValid()) {
                if (ShortcodeParser::$error_behavior == ShortcodeParser::ERROR) {
                    throw new \Exception('Couldn\'t decode HTML when processing short codes');
                } else {
                    $continue = false;
                }
            }
        }

        if ($continue) {
            // First, replace any shortcodes that are in attributes
            $this->replaceAttributeTagsWithContent($htmlvalue);

            // Find all the element scoped shortcode markers
            $shortcodes = $htmlvalue->query('//img[@class="' . ShortcodeParser::$marker_class . '"]');

            // Find the parents. Do this before DOM modification, since SPLIT might cause parents to move otherwise
            $parents = $this->findParentsForMarkers($shortcodes);

            /** @var DOMElement $shortcode */
            foreach ($shortcodes as $shortcode) {
                $tag = $tags[$shortcode->getAttribute('data-tagid')];
                $parent = $parents[$shortcode->getAttribute('data-parentid')];

                $class = null;
                if (!empty($tag['attrs']['location'])) {
                    $class = $tag['attrs']['location'];
                } elseif (!empty($tag['attrs']['class'])) {
                    $class = $tag['attrs']['class'];
                }

                $location = ShortcodeParser::INLINE;
                if ($class == 'left' || $class == 'right') {
                    $location = ShortcodeParser::BEFORE;
                }
                /**
                 * Below code disabled due to https://github.com/silverstripe/silverstripe-framework/issues/5987
                if ($class == 'center' || $class == 'leftAlone') {
                    $location = ShortcodeParser::SPLIT;
                }
                 */

                if (!$parent) {
                    if ($location !== ShortcodeParser::INLINE) {
                        throw new \RuntimeException(
                            "Parent block for shortcode couldn't be found, but location wasn't INLINE"
                        );
                    }
                } else {
                    $this->moveMarkerToCompliantHome($shortcode, $parent, $location);
                }

                $this->replaceMarkerWithContent($shortcode, $tag);
            }

            $content = $htmlvalue->getContent();

            // Clean up any marker classes left over, for example, those injected into <script> tags
            $parser = $this;
            $content = preg_replace_callback(
                // Not a general-case parser; assumes that the HTML generated in replaceElementTagsWithMarkers()
                // hasn't been heavily modified
                '/<img[^>]+class="' . preg_quote(ShortcodeParser::$marker_class) . '"[^>]+data-tagid="([^"]+)"[^>]*>/i',
                function ($matches) use ($tags, $parser) {
                    $tag = $tags[$matches[1]];
                    return $parser->getShortcodeReplacementText($tag);
                },
                $content ?? ''
            );
        }

        $this->extend('onAfterParse', $content);

        return $content;
    }
}
