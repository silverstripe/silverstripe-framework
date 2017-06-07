<?php

namespace SilverStripe\Control;

use SilverStripe\Core\Injector\Injectable;

/**
 * Represents a request processer that delegates pre and post request handling to nested request filters
 */
class RequestProcessor implements RequestFilter
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

    public function preRequest(HTTPRequest $request)
    {
        foreach ($this->filters as $filter) {
            $res = $filter->preRequest($request);
            if ($res === false) {
                return false;
            }
        }
        return null;
    }

    public function postRequest(HTTPRequest $request, HTTPResponse $response)
    {
        foreach ($this->filters as $filter) {
            $res = $filter->postRequest($request, $response);
            if ($res === false) {
                return false;
            }
        }
        return null;
    }
}
