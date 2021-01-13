<?php

namespace SilverStripe\Security;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\FieldList;

/**
 * Specialized subclass for disabled security tokens - always returns
 * TRUE for token checks. Use through {@link SecurityToken::disable()}.
 */
class NullSecurityToken extends SecurityToken
{

    /**
     * @param string $compare
     * @return bool
     */
    public function check($compare)
    {
        return true;
    }

    /**
     * @param HTTPRequest $request
     * @return Boolean
     */
    public function checkRequest($request)
    {
        return true;
    }

    /**
     * @param FieldList $fieldset
     * @return false
     */
    public function updateFieldSet(&$fieldset)
    {
        // Remove, in case it was added beforehand
        $fieldset->removeByName($this->getName());

        return false;
    }

    /**
     * @param string $url
     * @return string
     */
    public function addToUrl($url)
    {
        return $url;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return null;
    }

    /**
     * @param string $val
     */
    public function setValue($val)
    {
        // no-op
    }

    /**
     * @return string
     */
    public function generate()
    {
        return null;
    }
}
