<?php

namespace SilverStripe\ORM\FieldType;

use InvalidArgumentException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\NullableField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\ORM\DB;

/**
 * Represents a variable-length string of up to 16 megabytes, designed to store raw text
 *
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 *  "MyDescription" => "Text",
 * );
 * </code>
 *
 * @see DBHTMLText
 * @see DBHTMLVarchar
 * @see Varchar
 */
class DBText extends DBString
{

    private static $casting = [
        'BigSummary' => 'Text',
        'ContextSummary' => 'HTMLFragment', // Always returns HTML as it contains formatting and highlighting
        'FirstParagraph' => 'Text',
        'FirstSentence' => 'Text',
        'LimitSentences' => 'Text',
        'Summary' => 'Text',
    ];

    /**
     * Punctuation that marks an end of a sentence for the Summary() method
     */
    private static array $summary_sentence_separators = ['.', '?', '!'];

    /**
     * (non-PHPdoc)
     * @see DBField::requireField()
     */
    public function requireField()
    {
        $charset = Config::inst()->get(MySQLDatabase::class, 'charset');
        $collation = Config::inst()->get(MySQLDatabase::class, 'collation');

        $parts = [
            'datatype' => 'mediumtext',
            'character set' => $charset,
            'collate' => $collation,
            'default' => $this->defaultVal,
            'arrayValue' => $this->arrayValue
        ];

        $values = [
            'type' => 'text',
            'parts' => $parts
        ];

        DB::require_field($this->tableName, $this->name, $values);
    }

    /**
     * Limit sentences, can be controlled by passing an integer.
     *
     * @param int $maxSentences The amount of sentences you want.
     * @return string
     */
    public function LimitSentences($maxSentences = 2)
    {
        if (!is_numeric($maxSentences)) {
            throw new InvalidArgumentException("Text::LimitSentence() expects one numeric argument");
        }

        $value = $this->Plain();
        if (!$value) {
            return '';
        }

        // Do a word-search
        $words = preg_split('/\s+/u', $value ?? '') ?: [];
        $sentences = 0;
        foreach ($words as $i => $word) {
            if (preg_match('/(!|\?|\.)$/', $word ?? '') && !preg_match('/(Dr|Mr|Mrs|Ms|Miss|Sr|Jr|No)\.$/i', $word ?? '')) {
                $sentences++;
                if ($sentences >= $maxSentences) {
                    return implode(' ', array_slice($words ?? [], 0, $i + 1));
                }
            }
        }

        // Failing to find the number of sentences requested, fallback to a logical default
        if ($maxSentences > 1) {
            return $value;
        }
        // If searching for a single sentence (and there are none) just do a text summary
        return $this->Summary(20);
    }


    /**
     * Return the first string that finishes with a period (.) in this text.
     *
     * @return string
     */
    public function FirstSentence()
    {
        return $this->LimitSentences(1);
    }

    /**
     * Builds a basic summary, up to a maximum number of words
     *
     * @param int $maxWords
     * @param string|false $add
     * @return string
     */
    public function Summary($maxWords = 50, $add = false)
    {
        // Get plain-text version
        $value = $this->Plain();
        if (!$value) {
            return '';
        }

        // If no $elipsis string is provided, use the default one.
        if ($add === false) {
            $add = $this->defaultEllipsis();
        }

        // Split on sentences (don't remove punctuation)
        $summarySentenceSeparators = preg_quote(implode(static::config()->get('summary_sentence_separators')), '@');
        $possibleSentences = preg_split('@(?<=[' . $summarySentenceSeparators . '])@', $value ?? '') ?: [];
        $sentences = [];

        foreach ($possibleSentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence) {
                $sentences[] = $sentence;
            }
        }

        $wordCount = count(preg_split('#\s+#u', $sentences[0] ?? '') ?: []);

        // if the first sentence is too long, show only the first $maxWords words
        if ($wordCount > $maxWords) {
            return implode(' ', array_slice(explode(' ', $sentences[0] ?? ''), 0, $maxWords)) . $add;
        }

        // add each sentence while there are enough words to do so
        $result = '';
        do {
            // Add next sentence
            $result .= ' ' . array_shift($sentences);

            // If more sentences to process, count number of words
            if ($sentences) {
                $wordCount += count(preg_split('#\s+#u', $sentences[0] ?? '') ?: []);
            }
        } while ($wordCount < $maxWords && $sentences && trim($sentences[0] ?? ''));

        return trim($result ?? '');
    }

    /**
     * Get first paragraph
     *
     * @return string
     */
    public function FirstParagraph()
    {
        $value = $this->Plain();
        if (empty($value)) {
            return '';
        }

        // Split paragraphs and return first
        $paragraphs = preg_split('#\n{2,}#', $value ?? '') ?: [];
        return reset($paragraphs);
    }

    /**
     * Perform context searching to give some context to searches, optionally
     * highlighting the search term.
     *
     * @param int $characters Number of characters in the summary
     * @param string $keywords Supplied string ("keywords"). Will fall back to 'Search' querystring arg.
     * @param bool $highlight Add a highlight <mark> element around search query?
     * @param string|false $prefix Prefix text
     * @param string|false $suffix Suffix text
     * @return string HTML string with context
     */
    public function ContextSummary(
        $characters = 500,
        $keywords = null,
        $highlight = true,
        $prefix = false,
        $suffix = false
    ) {

        if (!$keywords) {
            // Use the default "Search" request variable (from SearchForm)
            $keywords = isset($_REQUEST['Search']) ? $_REQUEST['Search'] : '';
        }

        if ($prefix === false) {
            $prefix = $this->defaultEllipsis() . ' ';
        }

        if ($suffix === false) {
            $suffix = $this->defaultEllipsis();
        }

        // Get raw text value, but XML encode it (as we'll be merging with HTML tags soon)
        $text = Convert::raw2xml($this->Plain());
        $keywords = Convert::raw2xml($keywords);

        // Find the search string
        $position = empty($keywords) ? 0 : (int) mb_stripos($text ?? '', $keywords ?? '');

        // We want to search string to be in the middle of our block to give it some context
        $position = floor(max(0, $position - ($characters / 2)) ?? 0.0);

        if ($position > 0) {
            // We don't want to start mid-word
            $position = max(
                (int) mb_strrpos(mb_substr($text ?? '', 0, $position), ' '),
                (int) mb_strrpos(mb_substr($text ?? '', 0, $position), "\n")
            );
        }

        $summary = mb_substr($text ?? '', $position ?? 0, $characters);
        $stringPieces = explode(' ', $keywords ?? '');

        if ($highlight) {
            // Add a span around all key words from the search term as well
            if ($stringPieces) {
                foreach ($stringPieces as $stringPiece) {
                    if (mb_strlen($stringPiece ?? '') > 2) {
                        // Maintain case of original string
                        $summary = preg_replace(
                            '/' . preg_quote($stringPiece ?? '', '/') . '/i',
                            '<mark>$0</mark>',
                            $summary ?? ''
                        );
                    }
                }
            }
        }
        $summary = trim($summary ?? '');

        // Add leading / trailing '...' if trimmed on either end
        if ($position > 0) {
            $summary = $prefix . $summary;
        }
        if (strlen($text ?? '') > ($characters + $position)) {
            $summary = $summary . $suffix;
        }

        return nl2br($summary ?? '');
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        if (!$this->nullifyEmpty) {
            // Allow the user to select if it's null instead of automatically assuming empty string is
            return NullableField::create(TextareaField::create($this->name, $title));
        }
        // Automatically determine null (empty string)
        return TextareaField::create($this->name, $title);
    }

    public function scaffoldSearchField($title = null)
    {
        return new TextField($this->name, $title);
    }
}
