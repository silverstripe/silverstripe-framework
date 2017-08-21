<?php

namespace SilverStripe\Core\Tests\ObjectTest;

class T2 extends BaseObject
{
    protected $failover;
    protected $failoverArr = array();

    public function __construct()
    {
        $this->failover = new T1A();
        $this->failoverArr[0] = new T1B();
        $this->failoverArr[1] = new T1C();

        parent::__construct();
    }

    public function defineMethods()
    {
        $this->addWrapperMethod('Wrapping', 'wrappedMethod');

        $this->addMethodsFrom('failover');
        $this->addMethodsFrom('failoverArr', 0);
        $this->addMethodsFrom('failoverArr', 1);
        $this->addCallbackMethod('failoverCallback', function ($inst, $args) {
            return true;
        });
    }

    public function wrappedMethod($val)
    {
        return $val;
    }

    public function normalMethod()
    {
        return true;
    }
}
