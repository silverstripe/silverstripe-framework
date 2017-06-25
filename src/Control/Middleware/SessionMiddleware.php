<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;

class SessionMiddleware implements HTTPMiddleware
{

    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        try {
            // Start session and execute
            $request->getSession()->init($request);

            // Generate output
            $response = $delegate($request);

        // Save session data, even if there was an exception.
        // Note that save() will start/resume the session if required.
        } finally {
            $request->getSession()->save($request);
        }

        return $response;
    }
}
