<?php
namespace SilverStripe\Framework\Tests;

//whitespace here is important for tests, please don't change it
use SilverStripe\ORM\DataQuery;
use SilverStripe\Control\Controller  as  Cont ;
use SilverStripe\Control\HTTPRequest as Request, SilverStripe\Control\HTTPResponse as Response, SilverStripe\Security\PermissionProvider as P;
use silverstripe\test\ClassA;
use \SilverStripe\Core\ClassInfo;

class ClassI extends DataQuery implements P {
}
