<?php

namespace SilverStripe\Forms\GridField;

/**
 * Sometimes an action isn't enough: you need to provide additional support
 * URLs for the {@link GridField}.
 *
 * These URLs may return user-visible content, for example a pop-up form for
 * editing a record's details, or they may be support URLs for front-end
 * functionality.
 *
 * For example a URL that will return JSON-formatted data for a javascript
 * grid control.
 */
interface GridField_URLHandler extends GridFieldComponent
{

    /**
     * Return URLs to be handled by this grid field, in an array the same form
     * as $url_handlers.
     *
     * Handler methods will be called on the component, rather than the
     * {@link GridField}.
     *
     * @param GridField $gridField
     * @return array
     */
    public function getURLHandlers($gridField);
}
