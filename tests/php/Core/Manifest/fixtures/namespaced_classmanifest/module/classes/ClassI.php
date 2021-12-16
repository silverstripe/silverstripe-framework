<?php
/** @skipUpgrade */
namespace SilverStripe\Framework\Tests;

//whitespace here is important for tests, please don't change it
/** @skipUpgrade */
use SilverStripe\ORM\DataQuery;
/** @skipUpgrade */
use SilverStripe\Control\Controller  as  Cont ;
/** @skipUpgrade */
use SilverStripe\Control\HTTPRequest as Request, SilverStripe\Control\HTTPResponse as Response, SilverStripe\Security\PermissionProvider as P;
/** @skipUpgrade */
use silverstripe\test\ClassA;
/** @skipUpgrade */
use \SilverStripe\Core\ClassInfo;

/** @skipUpgrade */
class ClassI extends DataQuery implements P {
}
