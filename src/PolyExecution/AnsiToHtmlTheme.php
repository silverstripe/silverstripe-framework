<?php

namespace SilverStripe\PolyExecution;

use SensioLabs\AnsiConverter\Theme\Theme;
use SilverStripe\Core\Injector\Injectable;

/**
 * Theme for converting ANSI colours to something suitable in a browser against a white background
 */
class AnsiToHtmlTheme extends Theme
{
    use Injectable;

    public function asArray()
    {
        $colourMap = parent::asArray();
        $colourMap['cyan'] = 'royalblue';
        $colourMap['yellow'] = 'goldenrod';
        return $colourMap;
    }

    public function asArrayBackground()
    {
        $colourMap = parent::asArrayBackground();
        $colourMap['cyan'] = 'royalblue';
        $colourMap['yellow'] = 'goldenrod';
        return $colourMap;
    }
}
