<?php

namespace SilverStripe\PolyExecution;

use SensioLabs\AnsiConverter\AnsiToHtmlConverter as BaseAnsiConverter;
use SensioLabs\AnsiConverter\Theme\Theme;
use SilverStripe\Core\Injector\Injectable;

/**
 * Converts an ANSI text to HTML5 but doesn't give an opinionated default colour that isn't specified in the ANSI.
 */
class AnsiToHtmlConverter extends BaseAnsiConverter
{
    use Injectable;

    public function __construct(Theme $theme = null, $inlineStyles = true, $charset = 'UTF-8')
    {
        $theme ??= AnsiToHtmlTheme::create();
        parent::__construct($theme, $inlineStyles, $charset);
    }

    public function convert($text)
    {
        // remove cursor movement sequences
        $text = preg_replace('#\e\[(K|s|u|2J|2K|\d+(A|B|C|D|E|F|G|J|K|S|T)|\d+;\d+(H|f))#', '', $text);
        // remove character set sequences
        $text = preg_replace('#\e(\(|\))(A|B|[0-2])#', '', $text);

        $text = htmlspecialchars($text, PHP_VERSION_ID >= 50400 ? ENT_QUOTES | ENT_SUBSTITUTE : ENT_QUOTES, $this->charset);

        // convert hyperlinks to `<a>` tags (this is new to this subclass)
        $text = preg_replace('#\033]8;;(?<href>[^\033]*)\033\\\(?<text>[^\033]*)\033]8;;\033\\\#', '<a href="$1">$2</a>', $text);

        // carriage return
        $text = preg_replace('#^.*\r(?!\n)#m', '', $text);

        $tokens = $this->tokenize($text);

        // a backspace remove the previous character but only from a text token
        foreach ($tokens as $i => $token) {
            if ('backspace' == $token[0]) {
                $j = $i;
                while (--$j >= 0) {
                    if ('text' == $tokens[$j][0] && strlen($tokens[$j][1]) > 0) {
                        $tokens[$j][1] = substr($tokens[$j][1], 0, -1);

                        break;
                    }
                }
            }
        }

        $html = '';
        foreach ($tokens as $token) {
            if ('text' == $token[0]) {
                $html .= $token[1];
            } elseif ('color' == $token[0]) {
                $html .= $this->convertAnsiToColor($token[1]);
            }
        }

        // These lines commented out from the parent class implementation.
        // We don't want this opinionated default colouring - it doesn't appear in the ANSI format so it doesn't belong in the output.
        // if ($this->inlineStyles) {
        //     $html = sprintf('<span style="background-color: %s; color: %s">%s</span>', $this->inlineColors['black'], $this->inlineColors['white'], $html);
        // } else {
        //     $html = sprintf('<span class="ansi_color_bg_black ansi_color_fg_white">%s</span>', $html);
        // }
        // We do need an opening and closing span though, or the HTML markup is broken
        $html = '<span>' . $html . '</span>';

        // remove empty span
        $html = preg_replace('#<span[^>]*></span>#', '', $html);
        // remove unnecessary span
        $html = preg_replace('#<span>(.*?(?!</span>)[^<]*)</span>#', '$1', $html);

        return $html;
    }

    protected function convertAnsiToColor($ansi)
    {
        // Set $bg and $fg to null so we don't have a default opinionated colouring
        $bg = null;
        $fg = null;
        $style = [];
        $classes = [];
        if ('0' != $ansi && '' != $ansi) {
            $options = explode(';', $ansi);

            foreach ($options as $option) {
                if ($option >= 30 && $option < 38) {
                    $fg = $option - 30;
                } elseif ($option >= 40 && $option < 48) {
                    $bg = $option - 40;
                } elseif (39 == $option) {
                    $fg = null; // reset to default
                } elseif (49 == $option) {
                    $bg = null; // reset to default
                }
            }

            // options: bold => 1, underscore => 4, blink => 5, reverse => 7, conceal => 8
            if (in_array(1, $options)) {
                $style[] = 'font-weight: bold';
                $classes[] = 'ansi_bold';
            }

            if (in_array(4, $options)) {
                $style[] = 'text-decoration: underline';
                $classes[] = 'ansi_underline';
            }

            if (in_array(7, $options)) {
                $tmp = $fg;
                $fg = $bg;
                $bg = $tmp;
            }
        }

        // Biggest changes start here and go to the end of the method.
        // We're explicitly only setting the styling that was included in the ANSI formatting. The original applies
        // default colours regardless.
        if ($bg !== null) {
            $style[] = sprintf('background-color: %s', $this->inlineColors[$this->colorNames[$bg]]);
            $classes[] = sprintf('ansi_color_bg_%s', $this->colorNames[$bg]);
        }
        if ($fg !== null) {
            $style[] = sprintf('color: %s', $this->inlineColors[$this->colorNames[$fg]]);
            $classes[] = sprintf('ansi_color_fg_%s', $this->colorNames[$fg]);
        }

        if ($this->inlineStyles && !empty($style)) {
            return sprintf('</span><span style="%s">', implode('; ', $style));
        }
        if (!$this->inlineStyles && !empty($classes)) {
            return sprintf('</span><span class="%s">', implode('; ', $classes));
        }

        // Because of the way the parent class is implemented, we need to stop the old span and start a new one
        // even if we don't have any styling to apply.
        return '</span><span>';
    }
}
