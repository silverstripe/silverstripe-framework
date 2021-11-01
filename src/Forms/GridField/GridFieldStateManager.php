<?php


namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\HTTP;
use SilverStripe\Control\HTTPRequest;

/**
 * Creates a unique key for the gridfield, and uses that to write to and retrieve
 * its state from the request
 */
class GridFieldStateManager implements GridFieldStateManagerInterface
{
    /**
     * @param GridField $gridField
     * @return string
     */
    public function getStateKey(GridField $gridField): string
    {
        $i = 0;
        $form = $gridField->getForm();
        if ($form) {
            $controller = $form->getController();
            while ($controller instanceof GridFieldDetailForm_ItemRequest) {
                $controller = $controller->getController();
                $i++;
            }
        }

        return sprintf('%s-%s-%s', 'gridState', $gridField->getName(), $i);
    }

    /**
     * @param GridField $gridField
     * @param string $url
     * @return string
     */
    public function addStateToURL(GridField $gridField, string $url): string
    {
        $key = $this->getStateKey($gridField);
        $value = $gridField->getState(false)->Value();

        // Using a JSON-encoded empty array as the blank value, to avoid changing Value() semantics in a minor release
        if ($value === '{}') {
            return $url;
        }

        return HTTP::setGetVar($key, $value, $url);
    }

    /**
     * @param GridField $gridField
     * @param HTTPRequest $request
     * @return string|null
     */
    public function getStateFromRequest(GridField $gridField, HTTPRequest $request): ?string
    {
        return $request->requestVar($this->getStateKey($gridField));
    }
}
