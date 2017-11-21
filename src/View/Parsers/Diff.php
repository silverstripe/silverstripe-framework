<?php

namespace SilverStripe\View\Parsers;

use InvalidArgumentException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;

require_once 'difflib/difflib.php';

/**
 * Class representing a 'diff' between two sequences of strings.
 */
class Diff extends \Diff
{
    public static $html_cleaner_class = null;

    /**
     *  Attempt to clean invalid HTML, which messes up diffs.
     *  This cleans code if possible, using an instance of HTMLCleaner
     *
     *  NB: By default, only extremely simple tidying is performed,
     *  by passing through DomDocument::loadHTML and saveXML
     *
     * @param string $content HTML content
     * @param HTMLCleaner $cleaner Optional instance of a HTMLCleaner class to
     *    use, overriding self::$html_cleaner_class
     * @return mixed|string
     */
    public static function cleanHTML($content, $cleaner = null)
    {
        if (!$cleaner) {
            if (self::$html_cleaner_class && class_exists(self::$html_cleaner_class)) {
                $cleaner = Injector::inst()->create(self::$html_cleaner_class);
            } else {
                //load cleaner if the dependent class is available
                $cleaner = HTMLCleaner::inst();
            }
        }

        if ($cleaner) {
            $content = $cleaner->cleanHTML($content);
        } else {
            // At most basic level of cleaning, use DOMDocument to save valid XML.
            $doc = HTMLValue::create($content);
            $content = $doc->getContent();
        }

        // Remove empty <ins /> and <del /> tags because browsers hate them
        $content = preg_replace('/<(ins|del)[^>]*\/>/', '', $content);

        return $content;
    }

    /**
     * @param string $from
     * @param string $to
     * @param bool $escape
     * @return string
     */
    public static function compareHTML($from, $to, $escape = false)
    {
        // First split up the content into words and tags
        $set1 = self::getHTMLChunks($from);
        $set2 = self::getHTMLChunks($to);

        // Diff that
        $diff = new Diff($set1, $set2);

        $tagStack[1] = $tagStack[2] = 0;
        $rechunked[1] = $rechunked[2] = array();

        // Go through everything, converting edited tags (and their content) into single chunks.  Otherwise
        // the generated HTML gets crusty
        foreach ($diff->edits as $edit) {
            $lookForTag = false;
            $stuffFor = [];
            switch ($edit->type) {
                case 'copy':
                    $lookForTag = false;
                    $stuffFor[1] = $edit->orig;
                    $stuffFor[2] = $edit->orig;
                    break;

                case 'change':
                    $lookForTag = true;
                    $stuffFor[1] = $edit->orig;
                    $stuffFor[2] = $edit->final;
                    break;

                case 'add':
                    $lookForTag = true;
                    $stuffFor[1] = null;
                    $stuffFor[2] = $edit->final;
                    break;

                case 'delete':
                    $lookForTag = true;
                    $stuffFor[1] = $edit->orig;
                    $stuffFor[2] = null;
                    break;
            }

            foreach ($stuffFor as $listName => $chunks) {
                if ($chunks) {
                    foreach ($chunks as $item) {
                        // $tagStack > 0 indicates that we should be tag-building
                        if ($tagStack[$listName]) {
                            $rechunked[$listName][sizeof($rechunked[$listName])-1] .= ' ' . $item;
                        } else {
                            $rechunked[$listName][] = $item;
                        }

                        if ($lookForTag
                            && !$tagStack[$listName]
                            && isset($item[0])
                            && $item[0] == "<"
                            && substr($item, 0, 2) != "</"
                        ) {
                            $tagStack[$listName] = 1;
                        } elseif ($tagStack[$listName]) {
                            if (substr($item, 0, 2) == "</") {
                                $tagStack[$listName]--;
                            } elseif (isset($item[0]) && $item[0] == "<") {
                                $tagStack[$listName]++;
                            }
                        }
                    }
                }
            }
        }

        // Diff the re-chunked data, turning it into maked up HTML
        $diff = new Diff($rechunked[1], $rechunked[2]);
        $content = '';
        foreach ($diff->edits as $edit) {
            $orig = ($escape) ? Convert::raw2xml($edit->orig) : $edit->orig;
            $final = ($escape) ? Convert::raw2xml($edit->final) : $edit->final;

            switch ($edit->type) {
                case 'copy':
                    $content .= " " . implode(" ", $orig) . " ";
                    break;

                case 'change':
                    $content .= " <ins>" . implode(" ", $final) . "</ins> ";
                    $content .= " <del>" . implode(" ", $orig) . "</del> ";
                    break;

                case 'add':
                    $content .= " <ins>" . implode(" ", $final) . "</ins> ";
                    break;

                case 'delete':
                    $content .= " <del>" . implode(" ", $orig) . "</del> ";
                    break;
            }
        }

        return self::cleanHTML($content);
    }

    /**
     * @param string|bool|array $content If passed as an array, values will be concatenated with a comma.
     * @return array
     */
    public static function getHTMLChunks($content)
    {
        if ($content && !is_string($content) && !is_array($content) && !is_numeric($content) && !is_bool($content)) {
            throw new InvalidArgumentException('$content parameter needs to be a string or array');
        }
        if (is_bool($content)) {
            // Convert boolean to strings
            $content = $content ? "true" : "false";
        }
        if (is_array($content)) {
            // Convert array to CSV
            $content = implode(',', $content);
        }

        $content = str_replace(array("&nbsp;", "<", ">"), array(" "," <", "> "), $content);
        $candidateChunks = preg_split("/[\t\r\n ]+/", $content);
        $chunks = [];
        for ($i = 0; $i < count($candidateChunks); $i++) {
            $item = $candidateChunks[$i];
            if (isset($item[0]) && $item[0] == "<") {
                $newChunk = $item;
                while ($item[strlen($item)-1] != ">") {
                    if (++$i >= count($candidateChunks)) {
                        break;
                    }
                    $item = $candidateChunks[$i];
                    $newChunk .= ' ' . $item;
                }
                $chunks[] = $newChunk;
            } else {
                $chunks[] = $item;
            }
        }
        return $chunks;
    }
}
