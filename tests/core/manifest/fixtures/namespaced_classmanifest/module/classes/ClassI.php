<?php
namespace SilverStripe\Framework\Tests;

//whitespace here is important for tests, please don't change it
use ModelAdmin;
use Controller  as  Cont ;
use SS_HTTPRequest as Request, SS_HTTPResponse as Response, SilverStripe\Security\PermissionProvider as P;
use silverstripe\test\ClassA;
use \Object;


class ClassI extends ModelAdmin implements P {
}
