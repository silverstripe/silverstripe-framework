<?php

namespace SilverStripe\Dev\Validation;

use ReflectionException;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DatabaseAdmin;

/**
 * Hook up static validation to the deb/build process
 *
 * @extends Extension<DatabaseAdmin>
 * @deprecated 5.4.0 Will be renamed to DbBuildExtension
 */
class DatabaseAdminExtension extends Extension
{
    public function __construct()
    {
        Deprecation::noticeWithNoReplacment('5.4.0', 'Will be renamed to DbBuildExtension');
    }

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
