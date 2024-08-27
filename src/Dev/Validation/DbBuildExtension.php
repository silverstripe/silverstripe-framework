<?php

namespace SilverStripe\Dev\Validation;

use ReflectionException;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Command\DbBuild;

/**
 * Hook up static validation to the deb/build process
 *
 * @extends Extension<DbBuild>
 */
class DbBuildExtension extends Extension
{
    /**
     * Extension point in @see DbBuild::doBuild()
     *
     * @throws ReflectionException
     */
    protected function onAfterBuild(): void
    {
        $service = RelationValidationService::singleton();

        if (!$service->config()->get('output_enabled')) {
            return;
        }

        $service->executeValidation();
    }
}
