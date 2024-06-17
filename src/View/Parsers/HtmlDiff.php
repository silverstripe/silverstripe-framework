<?php

namespace SilverStripe\View\Parsers;

use LogicException;
use Masterminds\HTML5\Elements;
use SebastianBergmann\Diff\Differ;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;

/**
 * Class representing a 'diff' between two sequences of HTML strings.
 */
class HtmlDiff
{
    private const OLD_VAL = 'old';
    private const NEW_VAL = 'new';

    private static ?Differ $differ = null;

    public static ?string $html_cleaner_class = null;

    /**
     * Get a diff between two sets of HTML content. The result is an HTML fragment which can be added directly
     * into the DOM. <ins> elements are used to indicate new content, and <del> elements are used to indicate
     * removed content.
     *
     * @param bool $escape If true, the HTML in $from and $to will be escaped after the diff operation is performed.
     */
    public static function compareHtml(string|array $from, string|array $to, bool $escape = false): string
    {
        // Get HTML chunks even if we're going to escape it later
        // The diff algorithm sees "<span>some" as a single piece rather than "<span>" and "some" being separate
        $from = HtmlDiff::explodeToHtmlChunks($from);
        $to = HtmlDiff::explodeToHtmlChunks($to);

        // Diff the chunks
        $differ = HtmlDiff::getDiffer();
        $diff = $differ->diffToArray($from, $to);

        // If we aren't escaping the HTML, convert the first diff into clean HTML blocks and then run a new diff
        // on the blocks to get an end result that doesn't have broken HTML
        if (!$escape) {
            $diifAsBlocks = HtmlDiff::convertDiffToHtmlBlocks($diff);
            $diff = $differ->diffToArray($diifAsBlocks[HtmlDiff::OLD_VAL], $diifAsBlocks[HtmlDiff::NEW_VAL]);
        }

        $diff = HtmlDiff::createFinalDiffBlocks($diff, $escape);

        // Take the diff and slap the appropriate <ins> and <del> tags in place
        $content = '';
        foreach ($diff as $edit) {
            list($value, $type) = $edit;
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            if ($escape) {
                $value = Convert::raw2xml($value);
            }

            switch ($type) {
                case Differ::OLD:
                    $content .= ' ' . $value . ' ';
                    break;

                case Differ::ADDED:
                    $content .= ' <ins>' . $value . '</ins> ';
                    break;

                case Differ::REMOVED:
                    $content .= ' <del>' . $value . '</del> ';
                    break;

                default:
                    throw new LogicException('Unexpected type encountered: "' . (string)$type . '"');
            }
        }

        return HtmlDiff::cleanHTML($content);
    }

    /**
     * Takes a final diff and pulls the distinct tokens into related blocks
     * i.e. we avoid having multiple separate additions/subtractions in a row
     *
     * Similar to SebastianBergmann\Diff\Output\AbstractChunkOutputBuilder::getCommonChunks but it's HTML aware.
     */
    private static function createFinalDiffBlocks(array $diff, bool $escaped): array
    {
        $blocks = [];
        $building = null;
        $openTagsInBlock = 0;

        foreach ($diff as $edit) {
            list($value, $type) = $edit;
            $isClosingTag = !$escaped && str_starts_with($value, '</');
            $isOpeningNonVoidTag = !$escaped && HtmlDiff::isOpeningNonVoidTag($value);

            // If we were building a DIFFERENT type of block, or we've run out of open tags and are closing something
            // earlier in the chain, close the previous block and start a new one
            if ($building !== $type || ($isClosingTag && $openTagsInBlock <= 0)) {
                $building = $type;
                $openTagsInBlock = $isOpeningNonVoidTag ? 1 : 0;
                $blocks[] = [$value, $type];
                continue;
            }

            // Mark opened or closed blocks
            if ($isOpeningNonVoidTag) {
                $openTagsInBlock++;
            }
            if ($isClosingTag) {
                $openTagsInBlock--;
            }

            // Add this value to the current block
            $blocks[count($blocks) - 1][0] .= ' ' . $value;
        }

        return $blocks;
    }

    /**
     * Convert an intermediate diff into clean HTML blocks of changes.
     *
     * e.g. if making this change:
     * - <p>
     *     <span>
     * -     Some text
     * +     Other text
     *     </span>
     * - </p>
     *
     * We don't want to end up breaking up the HTML like this:
     * <del><p></del><span><del>Some</del><ins>Other</ins> text</span><del></p></del>
     * Instead we want to retain the valid HTML like this:
     * <del><p><span>Some text</span></p></del>
     * <ins><p>Other text</p></ins>
     */
    private static function convertDiffToHtmlBlocks(array $diff): array
    {
        $openTagsInBlock[HtmlDiff::OLD_VAL] = $openTagsInBlock[HtmlDiff::NEW_VAL] = 0;
        $htmlBlocks[HtmlDiff::OLD_VAL] = $htmlBlocks[HtmlDiff::NEW_VAL] = [];

        foreach ($diff as $edit) {
            list($value, $type) = $edit;
            switch ($type) {
                case Differ::OLD:
                    if ($value === '') {
                        break;
                    }
                    HtmlDiff::addToHtmlBlocks($htmlBlocks, $openTagsInBlock, HtmlDiff::OLD_VAL, false, $value);
                    HtmlDiff::addToHtmlBlocks($htmlBlocks, $openTagsInBlock, HtmlDiff::NEW_VAL, false, $value);
                    break;

                case Differ::ADDED:
                    HtmlDiff::addToHtmlBlocks($htmlBlocks, $openTagsInBlock, HtmlDiff::NEW_VAL, true, $value);
                    break;

                case Differ::REMOVED:
                    HtmlDiff::addToHtmlBlocks($htmlBlocks, $openTagsInBlock, HtmlDiff::OLD_VAL, true, $value);
                    break;
            }
        }
        return $htmlBlocks;
    }

    /**
     * Add an intermediate diff value to the appropriate HTML block
     */
    private static function addToHtmlBlocks(
        array &$htmlBlocks,
        array &$openTagsInBlock,
        string $oldOrNew,
        bool $lookForTag,
        string $value
    ): void {
        $alreadyMidBlock = $openTagsInBlock[$oldOrNew] > 0;
        $canAddTagsToBlock = $lookForTag || $alreadyMidBlock;

        if ($alreadyMidBlock) {
            // If we haven't closed all tags in the block, this value is part of the previous HTML block
            $htmlBlocks[$oldOrNew][count($htmlBlocks[$oldOrNew]) - 1] .= ' ' . $value;
        } else {
            // Otherwise it's part of a new block
            $htmlBlocks[$oldOrNew][] = $value;
        }

        if ($canAddTagsToBlock && HtmlDiff::isOpeningNonVoidTag($value)) {
            // If we're mid block or explicitly looking for new tags, we should add any new non-void tags to the block
            $openTagsInBlock[$oldOrNew]++;
        } elseif ($alreadyMidBlock && str_starts_with($value, '</')) {
            // If we're mid block and closing a tag, that's one less tag to close before the block ends
            $openTagsInBlock[$oldOrNew]--;
        }
    }

    private static function isOpeningNonVoidTag(string $value): bool
    {
        preg_match('/^<(\w*)[ >]/', $value, $matches);
        return isset($matches[1]) && Elements::isElement($matches[1]) && !Elements::isA($matches[1], Elements::VOID_TAG);
    }

    /**
     * Takes a long HTML string (or array of strings) and breaks it up into chunks of HTML
     * e.g. '<div class="something">Some Text</div>' becomes ['<div class="something">', 'Some', 'Text', '</div>']
     *
     * @param string|array $content If passed as an array, values will be concatenated with a comma.
     */
    private static function explodeToHtmlChunks(string|array $content): array
    {
        if (is_array($content)) {
            $content = array_filter($content, 'is_scalar');
            // Convert array to CSV
            $content = implode(',', $content);
        }

        $content = str_replace(['&nbsp;', '<', '>'], [' ',' <', '> '], $content);
        $candidateChunks = preg_split('/[\s]+/', $content);
        $chunks = [];
        $currentChunk = '';

        foreach ($candidateChunks as $item) {
            if ($item === '') {
                continue;
            }
            // If we've started a chunk, keep going until we close the tag.
            if ($currentChunk !== '') {
                $currentChunk .= ' ' . $item;
                if (!str_ends_with($item, '>')) {
                    continue;
                } else {
                    $chunks[] = $currentChunk;
                    $currentChunk = '';
                    continue;
                }
            }

            // If we open a tag, start a new chunk.
            if (str_starts_with($item, '<') && !str_ends_with($item, '>')) {
                $currentChunk = $item;
                continue;
            }

            // If we're not starting or continuing a tag chunk, just add this as its own chunk.
            $chunks[] = $item;
        }

        return $chunks;
    }

    /**
     *  Attempt to clean invalid HTML, which messes up diffs.
     *  This cleans code if possible, using an instance of HTMLCleaner
     *
     * @param ?HTMLCleaner $cleaner Optional instance of a HTMLCleaner class to
     *    use, overriding HtmlDiff::$html_cleaner_class
     */
    private static function cleanHTML(string $content, ?HTMLCleaner $cleaner = null): string
    {
        if (!$cleaner) {
            if (HtmlDiff::$html_cleaner_class && class_exists(HtmlDiff::$html_cleaner_class)) {
                $cleaner = Injector::inst()->create(HtmlDiff::$html_cleaner_class);
            } else {
                //load cleaner if the dependent class is available
                $cleaner = HTMLCleaner::inst();
            }
        }

        /** @var HTMLCleaner $cleaner */
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

    private static function getDiffer(): Differ
    {
        if (!HtmlDiff::$differ) {
            HtmlDiff::$differ = new Differ();
        }
        return HtmlDiff::$differ;
    }
}
