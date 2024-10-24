<?php

namespace SilverStripe\PolyExecution;

use InvalidArgumentException;
use LogicException;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use SilverStripe\Core\Injector\Injectable;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Output that correctly formats for HTML or for the terminal, depending on the output type.
 * Used for functionality that can be used both via CLI and via the browser.
 */
class PolyOutput extends Output
{
    use Injectable;

    public const LIST_UNORDERED = 'ul';
    public const LIST_ORDERED = 'ol';

    /** Use this if you want HTML markup in the output */
    public const FORMAT_HTML = 'Html';
    /** Use this for outputing to a terminal, or for plain text output */
    public const FORMAT_ANSI = 'Ansi';

    private string $outputFormat;

    private ?OutputInterface $wrappedOutput = null;

    private ?AnsiToHtmlConverter $ansiConverter = null;

    /**
     * Array of list types that are opened, and the options that were used to open them.
     */
    private array $listTypeStack = [];

    /**
     * @param string $outputFormat The format to use for the output (one of the FORMAT_* constants)
     * @param int The verbosity level (one of the VERBOSITY_* constants in OutputInterface)
     * @param boolean $decorated Whether to decorate messages (if false, decoration tags will simply be removed)
     * @param OutputInterface|null $wrappedOutput An optional output pipe messages through.
     * Useful for capturing output instead of echoing directly to the client, for example.
     */
    public function __construct(
        string $outputFormat,
        int $verbosity = OutputInterface::VERBOSITY_NORMAL,
        bool $decorated = false,
        ?OutputInterface $wrappedOutput = null
    ) {
        $this->setOutputFormat($outputFormat);
        // Intentionally don't call parent constructor, because it doesn't use the setter methods.
        if ($wrappedOutput) {
            $this->setWrappedOutput($wrappedOutput);
        } else {
            $this->setFormatter(new OutputFormatter());
        }
        $this->setDecorated($decorated);
        $this->setVerbosity($verbosity);
    }

    /**
     * Writes messages to the output - but only if we're using HTML format.
     * Useful for when HTML and ANSI formatted output need to diverge.
     *
     * Note that this method uses RAW output by default, which allows you to add HTML markup
     * directly into the message. If you're using symfony/console style formatting, set
     * $options to use the OUTPUT_NORMAL constant.
     *
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants),
     * 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function writeForHtml(
        string|iterable $messages,
        bool $newline = false,
        int $options = OutputInterface::OUTPUT_RAW
    ): void {
        if ($this->outputFormat === PolyOutput::FORMAT_HTML) {
            $this->write($messages, $newline, $options);
        }
    }

    /**
     * Writes messages to the output - but only if we're using ANSI format.
     * Useful for when HTML and ANSI formatted output need to diverge.
     *
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants),
     * 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function writeForAnsi(
        string|iterable $messages,
        bool $newline = false,
        int $options = OutputInterface::OUTPUT_NORMAL
    ): void {
        if ($this->outputFormat === PolyOutput::FORMAT_ANSI) {
            $this->write($messages, $newline, $options);
        }
    }

    /**
     * Start a list.
     * In HTML format this will write the opening `<ul>` or `<ol>` tag.
     * In ANSI format this will set up information for rendering list items.
     *
     * Call writeListItem() to add items to the list, then call stopList() when you're done.
     *
     * @param string $listType One of the LIST_* consts, e.g. PolyOutput::LIST_UNORDERED
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants),
     * 0 is considered the same as self::OUTPUT_NORMAL | self::VERBOSITY_NORMAL
     */
    public function startList(string $listType = PolyOutput::LIST_UNORDERED, int $options = OutputInterface::OUTPUT_NORMAL): void
    {
        $this->listTypeStack[] = ['type' => $listType, 'options' => $options];
        if ($this->outputFormat === PolyOutput::FORMAT_HTML) {
            $this->write("<{$listType}>", options: $this->forceRawOutput($options));
        }
    }

    /**
     * Stop a list.
     * In HTML format this will write the closing `</ul>` or `</ol>` tag.
     * In ANSI format this will mark the list as closed (useful when nesting lists)
     */
    public function stopList(): void
    {
        if (empty($this->listTypeStack)) {
            throw new LogicException('No list to close.');
        }
        $info = array_pop($this->listTypeStack);
        if ($this->outputFormat === PolyOutput::FORMAT_HTML) {
            $this->write("</{$info['type']}>", options: $this->forceRawOutput($info['options']));
        }
    }

    /**
     * Writes messages formatted as a list.
     * Make sure to call startList() before writing list items, and call stopList() when you're done.
     *
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants),
     * by default this will inherit the options used to start the list.
     */
    public function writeListItem(string|iterable $items, ?int $options = null): void
    {
        if (empty($this->listTypeStack)) {
            throw new LogicException('No lists started. Call startList() first.');
        }
        if (is_string($items)) {
            $items = [$items];
        }
        $method = "writeListItem{$this->outputFormat}";
        $this->$method($items, $options);
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        if ($this->outputFormat === PolyOutput::FORMAT_HTML) {
            $formatter = HtmlOutputFormatter::create($formatter);
        }
        parent::setFormatter($formatter);
    }

    /**
     * Set whether this will output in HTML or ANSI format.
     *
     * @throws InvalidArgumentException if the format isn't one of the FORMAT_* constants
     */
    public function setOutputFormat(string $outputFormat): void
    {
        if (!in_array($outputFormat, [PolyOutput::FORMAT_ANSI, PolyOutput::FORMAT_HTML])) {
            throw new InvalidArgumentException("Unexpected format - got '$outputFormat'.");
        }
        $this->outputFormat = $outputFormat;
    }

    /**
     * Get the format used for output.
     */
    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    /**
     * Set an output to wrap inside this one. Useful for capturing output in a buffer.
     */
    public function setWrappedOutput(OutputInterface $wrappedOutput): void
    {
        $this->wrappedOutput = $wrappedOutput;
        $this->setFormatter($this->wrappedOutput->getFormatter());
        // Give wrapped output a debug verbosity - that way it'll output everything we tell it to.
        // Actual verbosity is handled by PolyOutput's parent Output class.
        $this->wrappedOutput->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    }

    protected function doWrite(string $message, bool $newline): void
    {
        if ($this->outputFormat === PolyOutput::FORMAT_HTML) {
            $output = $message . ($newline ? '<br>' . PHP_EOL : '');
        } else {
            $output = $message . ($newline ? PHP_EOL : '');
        }
        if ($this->wrappedOutput) {
            $this->wrappedOutput->write($output, options: OutputInterface::OUTPUT_RAW);
        } else {
            echo $output;
        }
    }

    private function writeListItemHtml(iterable $items, ?int $options): void
    {
        if ($options === null) {
            $listInfo = $this->listTypeStack[array_key_last($this->listTypeStack)];
            $options = $listInfo['options'];
        }
        foreach ($items as $item) {
            $this->write('<li>', options: $this->forceRawOutput($options));
            $this->write($item, options: $options);
            $this->write('</li>', options: $this->forceRawOutput($options));
        }
    }

    private function writeListItemAnsi(iterable $items, ?int $options): void
    {
        $listInfo = $this->listTypeStack[array_key_last($this->listTypeStack)];
        $listType = $listInfo['type'];
        if ($options === null) {
            $options = $listInfo['options'];
        }
        foreach ($items as $i => $item) {
            switch ($listType) {
                case PolyOutput::LIST_UNORDERED:
                    $bullet = '*';
                    break;
                case PolyOutput::LIST_ORDERED:
                    // Start at 1
                    $numberOffset = $listInfo['offset'] ?? 1;
                    $bullet = ($i + $numberOffset) . '.';
                    break;
                default:
                    throw new InvalidArgumentException("Unexpected list type - got '$listType'.");
            }
            $indent = str_repeat(' ', count($this->listTypeStack));
            $this->writeln("{$indent}{$bullet} {$item}", $options);
        }
        // Update the number offset so the next item in the list has the correct number
        if ($listType === PolyOutput::LIST_ORDERED) {
            $this->listTypeStack[array_key_last($this->listTypeStack)]['offset'] = $numberOffset + $i + 1;
        }
    }

    private function getVerbosityOption(int $options): int
    {
        // Logic copied from Output::write() - uses bitwise operations to separate verbosity from output type.
        $verbosities = OutputInterface::VERBOSITY_QUIET | OutputInterface::VERBOSITY_NORMAL | OutputInterface::VERBOSITY_VERBOSE | OutputInterface::VERBOSITY_VERY_VERBOSE | OutputInterface::VERBOSITY_DEBUG;
        return $verbosities & $options ?: OutputInterface::VERBOSITY_NORMAL;
    }

    private function forceRawOutput(int $options): int
    {
        return $this->getVerbosityOption($options) | OutputInterface::OUTPUT_RAW;
    }
}
