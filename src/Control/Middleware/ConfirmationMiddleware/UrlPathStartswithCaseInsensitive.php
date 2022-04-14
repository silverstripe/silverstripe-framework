<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Confirmation;

/**
 * A case insensitive rule to match beginning of URL
 */
class UrlPathStartswithCaseInsensitive extends UrlPathStartswith
{
    protected function checkPath($path)
    {
        $pattern = $this->getPath();

        $mb_path = mb_strcut($this->normalisePath($path) ?? '', 0, strlen($pattern ?? ''));
        return mb_stripos($mb_path ?? '', $pattern ?? '') === 0;
    }
}
