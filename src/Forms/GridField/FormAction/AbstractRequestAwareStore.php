<?php
namespace SilverStripe\Forms\GridField\FormAction;

use SilverStripe\Control\HTTPRequest;

abstract class AbstractRequestAwareStore implements StateStore
{
    private static $dependencies = [
        'request' => '%$' . HTTPRequest::class,
    ];

    /**
     * @var HTTPRequest
     */
    protected $request;

    /**
     * @return HTTPRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param HTTPRequest $request
     * @return $this
     */
    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }
}
