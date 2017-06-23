<?php

namespace SilverStripe\Control;

class SessionMiddleware implements HTTPMiddleware
{

    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        try {
            // Start session and execute
            $request->getSession()->init();

            // Generate output
            $response = $delegate($request);

        // Save session data, even if there was an exception.
        // Note that save() will start/resume the session if required.
        } finally {
            $request->getSession()->save();
        }

        return $response;
    }
}
