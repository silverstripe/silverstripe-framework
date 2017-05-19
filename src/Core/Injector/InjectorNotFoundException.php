<?php

namespace SilverStripe\Core\Injector;

use Psr\Container\NotFoundExceptionInterface;

class InjectorNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
