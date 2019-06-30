<?php declare(strict_types = 1);

namespace SilverStripe\Core\Injector;

use Psr\Container\NotFoundExceptionInterface;

class InjectorNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
