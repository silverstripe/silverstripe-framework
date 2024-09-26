<?php

namespace SilverStripe\PolyExecution;

use SilverStripe\Core\Injector\Injectable;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyleInterface;

/**
 * Wraps an ANSI formatter and converts the ANSI formatting to styled HTML.
 */
class HtmlOutputFormatter implements OutputFormatterInterface
{
    use Injectable;

    private OutputFormatterInterface $ansiFormatter;
    private AnsiToHtmlConverter $ansiConverter;

    public function __construct(OutputFormatterInterface $formatter)
    {
        $this->ansiFormatter = $formatter;
        $this->ansiConverter = AnsiToHtmlConverter::create();
    }

    public function setDecorated(bool $decorated): void
    {
        $this->ansiFormatter->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->ansiFormatter->isDecorated();
    }

    public function setStyle(string $name, OutputFormatterStyleInterface $style): void
    {
        $this->ansiFormatter->setStyle($name, $style);
    }

    public function hasStyle(string $name): bool
    {
        return $this->ansiFormatter->hasStyle($name);
    }

    public function getStyle(string $name): OutputFormatterStyleInterface
    {
        return $this->ansiFormatter->getStyle($name);
    }

    public function format(?string $message): ?string
    {
        $formatted = $this->ansiFormatter->format($message);
        if ($this->isDecorated()) {
            return $this->ansiConverter->convert($formatted);
        }
        return $formatted;
    }
}
