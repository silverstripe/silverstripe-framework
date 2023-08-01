<?php

namespace SilverStripe\Control\Email;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Factory;
use Symfony\Component\Mailer\Transport;

/**
 * Creates an email transport from a DSN string
 * A DSN defined in an environment variable has priority over a DSN defined in yml config file
 */
class TransportFactory implements Factory
{
    public function create($service, array $params = [])
    {
        $dsn = Environment::getEnv('MAILER_DSN') ?: $params['dsn'];
        $dispatcher = $params['dispatcher'];
        return Transport::fromDsn($dsn, $dispatcher);
    }
}
