<?php

namespace SilverStripe\Control;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\Deprecation;

/**
 * Middleware that provides back-support for the deprecated RequestFilter API.
 * You should use HTTPMiddleware directly instead.
 */
class RequestProcessor implements HTTPMiddleware
{
    use Injectable;

    /**
     * List of currently assigned request filters
     *
     * @var array
     */
    private $filters = array();

    public function __construct($filters = array())
    {
        $this->filters = $filters;
    }

    /**
     * Assign a list of request filters
     *
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        if ($this->filters) {
            Deprecation::notice(
                '4.0',
                'Deprecated RequestFilters are in use. Apply HTTPMiddleware to Director.middlewares instead.'
            );
        }

        foreach ($this->filters as $filter) {
            $res = $filter->preRequest($request);
            if ($res === false) {
                return new HTTPResponse(_t(__CLASS__.'.INVALID_REQUEST', 'Invalid request'), 400);
            }
        }

        $response = $delegate($request);

        foreach ($this->filters as $filter) {
            $res = $filter->postRequest($request, $response);
            if ($res === false) {
                return new HTTPResponse(_t(__CLASS__ . '.REQUEST_ABORTED', 'Request aborted'), 500);
            }
        }

        return $response;
    }
}
