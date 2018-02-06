<?php

namespace SilverStripe\Control;

use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Dev\Deprecation;

/**
 * Middleware that provides back-support for the deprecated RequestFilter API.
 *
 * @deprecated 4.0..5.0 Use HTTPMiddleware directly instead.
 */
class RequestProcessor implements HTTPMiddleware
{
    use Injectable;

    /**
     * List of currently assigned request filters
     *
     * @var RequestFilter[]
     */
    private $filters = array();

    /**
     * Construct new RequestFilter with a list of filter objects
     *
     * @param RequestFilter[] $filters
     */
    public function __construct($filters = array())
    {
        $this->filters = $filters;
    }

    /**
     * Assign a list of request filters
     *
     * @param RequestFilter[] $filters
     * @return $this
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        if ($this->filters) {
            Deprecation::notice(
                '5.0',
                'Deprecated RequestFilters are in use. Apply HTTPMiddleware to Director.middlewares instead.'
            );
        }

        foreach ($this->filters as $filter) {
            $res = $filter->preRequest($request);
            if ($res === false) {
                return new HTTPResponse(_t(__CLASS__ . '.INVALID_REQUEST', 'Invalid request'), 400);
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
