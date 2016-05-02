<?php
namespace SilverStripe\Framework\Tests;

//whitespace here is important for tests, please don't change it
use  ModelAdmin;
use Controller  as  Cont ;
use SS_HTTPRequest as Request,SS_HTTPResponse AS Response, PermissionProvider AS P;
use silverstripe\test\ClassA;
use \DataObject;

class ClassI extends ModelAdmin implements P {
}
