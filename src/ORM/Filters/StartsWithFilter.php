<?php declare(strict_types = 1);

namespace SilverStripe\ORM\Filters;

/**
 * Matches textual content with a substring match from the beginning
 * of the string.
 *
 * <code>
 *  "abcdefg" => "defg" # false
 *  "abcdefg" => "abcd" # true
 * </code>
 */
class StartsWithFilter extends PartialMatchFilter
{

    protected function getMatchPattern($value)
    {
        return "$value%";
    }
}
