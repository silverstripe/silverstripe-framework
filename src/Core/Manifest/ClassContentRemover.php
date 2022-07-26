<?php

namespace SilverStripe\Core\Manifest;

/**
 * Class ClassContentRemover
 * @package SilverStripe\Core\Manifest
 *
 * A utility class to clean the contents of a PHP file containing classes/interfaces/traits/enums.
 *
 * It removes any code with in `$cut_off_depth` number of curly braces.
 */
class ClassContentRemover
{

    /**
     * @param string $filePath
     * @param int $cutOffDepth The number of levels of curly braces to go before ignoring the content
     *
     * @return string
     */
    public static function remove_class_content($filePath, $cutOffDepth = 1)
    {

        // Use PHP's built in method to strip comments and whitespace
        $contents = php_strip_whitespace($filePath ?? '');

        if (!trim($contents ?? '')) {
            return $contents;
        }

        if (!preg_match('/\b(?:class|interface|trait|enum)/i', $contents ?? '')) {
            return '';
        }

        // tokenize the file contents
        $tokens = token_get_all($contents ?? '');
        $cleanContents = '';
        $depth = 0;
        $startCounting = false;
        // iterate over all the tokens and only store the tokens that are outside $cutOffDepth
        foreach ($tokens as $token) {
            // only store the string literal of the token, that's all we need
            if (!is_array($token)) {
                $token = [
                    T_STRING,
                    $token,
                    null
                ];
            }

            // only count if we see a class/interface/trait keyword
            $targetTokens = [T_CLASS, T_INTERFACE, T_TRAIT];
            if (version_compare(phpversion(), '8.1.0', '>')) {
                $targetTokens[] = T_ENUM;
            }

            if (!$startCounting && in_array($token[0], $targetTokens)) {
                $startCounting = true;
            }

            // use curly braces as a sign of depth
            if ($token[1] === '{') {
                if ($depth < $cutOffDepth) {
                    $cleanContents .= $token[1];
                }
                if ($startCounting) {
                    ++$depth;
                }
            } elseif ($token[1] === '}') {
                if ($startCounting) {
                    --$depth;

                    // stop counting if we've just come out of the
                    // class/interface/trait declaration
                    if ($depth <= 0) {
                        $startCounting = false;
                    }
                }
                if ($depth < $cutOffDepth) {
                    $cleanContents .= $token[1];
                }
            } elseif ($depth < $cutOffDepth) {
                $cleanContents .= $token[1];
            }
        }

        // return cleaned class
        return trim($cleanContents ?? '');
    }
}
