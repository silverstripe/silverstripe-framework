<?php

namespace SilverStripe\Dev\Validation;

use ReflectionException;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DatabaseAdmin;

/**
 * Hook up static validation to the deb/build process
 *
 * @extends Extension<DatabaseAdmin>
 */
class DatabaseAdminExtension extends Extension
{
    /**
     * Extension point in @see DatabaseAdmin::doBuild()
     *
     * @param bool $quiet
     * @param bool $populate
     * @param bool $testMode
     * @throws ReflectionException
     */
    public function onAfterBuild(bool $quiet, bool $populate, bool $testMode): void
    {
        $service = RelationValidationService::singleton();

        if (!$service->config()->get('output_enabled')) {
            return;
        }

        $service->executeValidation();
    }
}
