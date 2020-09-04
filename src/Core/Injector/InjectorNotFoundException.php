<?php

namespace SilverStripe\Core\Injector;

use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class InjectorNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
