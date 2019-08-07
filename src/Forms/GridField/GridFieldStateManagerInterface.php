<?php


namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\HTTPRequest;

/**
 * Defines a class that can create a key for a gridfield and apply its
 * state to a request, and consume state from the request
 */
interface GridFieldStateManagerInterface
{
    /**
     * @param GridField $gridField
     * @return string
     */
    public function getStateKey(GridField $gridField): string;

    /**
     * @param GridField $gridField
     * @param string $url
     * @return string
     */
    public function addStateToURL(GridField $gridField, string $url): string;

    /**
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return string|null
     */
    public function getStateFromRequest(GridField $gridField, HTTPRequest $request): ?string;
}
