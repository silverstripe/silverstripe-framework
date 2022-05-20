<?php

namespace SilverStripe\View\Parsers;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Support class for converting unicode strings into a suitable 7-bit ASCII equivalent.
 *
 * Usage:
 *
 * <code>
 * $tr = new Transliterator();
 * $ascii = $tr->toASCII($unicode);
 * </code>
 */
class Transliterator
{
    use Injectable;
    use Configurable;

    /**
     * @config
     * @var boolean Allow the use of iconv() to perform transliteration.  Set to false to disable.
     * Even if this variable is true, iconv() won't be used if it's not installed.
     */
    private static $use_iconv = false;

    /**
     * Convert the given utf8 string to a safe ASCII source
     *
     * @param string $source
     * @return string
     */
    public function toASCII($source)
    {
        if (function_exists('iconv') && $this->config()->use_iconv) {
            return $this->useIconv($source);
        } else {
            return $this->useStrTr($source);
        }
    }

    /**
     * Transliteration using strtr() and a lookup table
     *
     * @param string $source
     * @return string
     */
    protected function useStrTr($source)
    {
        $table = [
            'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
            'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'Ae', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
            'Õ'=>'O', 'Ö'=>'Oe', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'Ue', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'ss',
            'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'ae', 'å'=>'a', 'æ'=>'ae', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
            'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
            'ô'=>'o', 'õ'=>'o', 'ö'=>'oe', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'ue', 'ý'=>'y',
            'þ'=>'b', 'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
            'Ā'=>'A', 'ā'=>'a', 'Ē'=>'E', 'ē'=>'e', 'Ī'=>'I', 'ī'=>'i', 'Ō'=>'O', 'ō'=>'o', 'Ū'=>'U', 'ū'=>'u',
            'œ'=>'oe', 'ĳ'=>'ij', 'ą'=>'a','ę'=>'e', 'ė'=>'e', 'į'=>'i', 'ų'=>'u', 'Ą'=>'A',
            'Ę'=>'E', 'Ė'=>'E', 'Į'=>'I','Ų'=>'U',
            "ľ"=>"l", "Ľ"=>"L", "ť"=>"t", "Ť"=>"T", "ů"=>"u", "Ů"=>"U",
            'ł'=>'l', 'Ł'=>'L', 'ń'=>'n', 'Ń'=>'N', 'ś'=>'s', 'Ś'=>'S', 'ź'=>'z', 'Ź'=>'Z', 'ż'=>'z', 'Ż'=>'Z',
            'а'=>"a",'б'=>"b",'в'=>"v",'г'=>"g",'д'=>"d",'е'=>"e",'ё'=>"yo",'ж'=>"zh",'з'=>"z",'и'=>"i",
            'й'=>"y",'к'=>"k",'л'=>"l",'м'=>"m",'н'=>"n",'о'=>"o",'п'=>"p",'р'=>"r",'с'=>"s",'т'=>"t",
            'у'=>"u",'ф'=>"f",'х'=>"kh",'ц'=>"ts",'ч'=>"ch",'ш'=>"sh",'щ'=>"shch",'ы'=>"y",'э'=>"e",'ю'=>"yu",
            'я'=>"ya",
            'А'=>"A",'Б'=>"B",'В'=>"V",'Г'=>"G",'Д'=>"D",'Е'=>"E",'Ё'=>"YO",'Ж'=>"ZH",'З'=>"Z",'И'=>"I",
            'Й'=>"Y",'К'=>"K",'Л'=>"L",'М'=>"M",'Н'=>"N",'О'=>"O",'П'=>"P",'Р'=>"R",'С'=>"S",'Т'=>"T",
            'У'=>"U",'Ф'=>"F",'Х'=>"KH",'Ц'=>"TS",'Ч'=>"CH",'Ш'=>"SH",'Щ'=>"SHCH",'Ы'=>"Y",'Э'=>"E",'Ю'=>"YU",
            'Я'=>"YA",
            'α'=>'a', 'Α'=>'A', 'ά'=>'a', 'Ά'=>'A', 'β'=>'v', 'Β'=>'V', 'γ'=>'g', 'Γ'=>'G', 'δ'=>'d', 'Δ'=>'D',
            'ε'=>'e', 'ϵ'=>'e', 'Ε'=>'E', 'έ'=>'e', 'Έ'=>'E', 'ζ'=>'z', 'Ζ'=>'Z', 'η'=>'i', 'Η'=>'I',
            'θ'=>'th', 'ϑ' => 'th', 'Θ'=>'TH', 'ι'=>'i', 'Ι'=>'I', 'ί'=>'i', 'Ί'=>'I', 'κ'=>'k', 'ϰ'=>'k', 'Κ'=>'K',
            'λ'=>'l', 'Λ'=>'L', 'μ'=>'m', 'Μ'=>'M', 'ν'=>'n', 'Ν'=>'N', 'ή'=>'n', 'Ή'=>'N', 'ἠ'=>'n', 'Ἠ'=>'N',
            'ο'=>'o', 'Ο'=>'O', 'ό'=>'o', 'Ό'=>'O', 'π'=>'p', 'Π'=>'P', 'ρ'=>'r', 'ϱ'=>'r', 'Ρ'=>'R', 'ῤ'=>'rh',
            'σ'=>'s', 'ς'=>'s', 'Σ'=>'S', 'τ'=>'t', 'Τ'=>'T', 'υ'=>'y', 'Υ'=>'Y', 'ύ'=>'y', 'Ύ'=>'Y', 'ὐ'=>'y',
            'φ'=>'f', 'ϕ'=>'f', 'Φ'=>'F', 'χ'=>'ch', 'Χ'=>'CH', 'ψ'=>'ps', 'Ψ'=>'PS', 'ξ'=>'x', 'Ξ'=>'X',
            'ω'=>'w', 'Ω'=>'W', 'ώ'=>'o', 'Ώ'=>'O', 'ὠ'=>'o', 'Ὠ'=>'O',
        ];

        return strtr($source ?? '', $table ?? '');
    }

    /**
     * Transliteration using iconv()
     *
     * @param string $source
     * @return string
     */
    protected function useIconv($source)
    {
        return iconv("utf-8", "us-ascii//IGNORE//TRANSLIT", $source ?? '');
    }
}
