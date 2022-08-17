<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Confirmation;

/**
 * A rule to match beginning of URL
 */
class UrlPathStartswith implements Rule, Bypass
{
    use PathAware;

    /**
     * Initialize the rule with the path
     *
     * @param string $path
     */
    public function __construct(string $path): void
    {
        $this->setPath($path);
    }

    /**
     * Generates the confirmation item
     *
     * @param string $token
     * @param string $url
     *
     * @return Confirmation\Item
     */
    protected function buildConfirmationItem(string $token, string $url): SilverStripe\Security\Confirmation\Item
    {
        return new Confirmation\Item(
            $token,
            _t(__CLASS__ . '.CONFIRMATION_NAME', 'URL begins with "{path}"', ['path' => $this->getPath()]),
            _t(__CLASS__ . '.CONFIRMATION_DESCRIPTION', 'The complete URL is: "{url}"', ['url' => $url])
        );
    }

    /**
     * Generates the unique token depending on the path
     *
     * @param string $path URL path
     *
     * @return string
     */
    protected function generateToken(string $path): string
    {
        return sprintf('%s::%s', static::class, $path);
    }

    /**
     * Checks the given path by the rules and
     * returns whether it should be protected
     *
     * @param string $path Path to be checked
     *
     * @return bool
     */
    protected function checkPath(string $path): bool
    {
        $targetPath = $this->getPath();
        return strncmp($this->normalisePath($path) ?? '', $targetPath ?? '', strlen($targetPath ?? '')) === 0;
    }

    public function checkRequestForBypass(HTTPRequest $request): bool
    {
        return $this->checkPath($request->getURL());
    }

    public function getRequestConfirmationItem(HTTPRequest $request): SilverStripe\Security\Confirmation\Item|null
    {
        if (!$this->checkPath($request->getURL())) {
            return null;
        }

        $token = $this->generateToken($this->getPath());

        return $this->buildConfirmationItem($token, $request->getURL(true));
    }
}
