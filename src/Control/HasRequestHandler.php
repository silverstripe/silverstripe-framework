<?php

namespace SilverStripe\Control;

/**
 * Indicator for a class which cannot handle requests directly, but is able to
 * generate a delegate for those requests.
 */
interface HasRequestHandler
{

    /**
     * @return RequestHandler
     */
    public function getRequestHandler();
}
