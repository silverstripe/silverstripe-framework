<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;

class DevBuildController extends Controller implements PermissionProvider
{

    private static $url_handlers = [
        '' => 'build'
    ];

    private static $allowed_actions = [
        'build'
    ];

    private static $init_permissions = [
        'ADMIN',
        'ALL_DEV_ADMIN',
        'CAN_DEV_BUILD',
    ];

    protected function init(): void
    {
        parent::init();

        if (!$this->canInit()) {
            Security::permissionFailure($this);
        }
    }

    public function build(HTTPRequest $request): HTTPResponse
    {
        if (Director::is_cli()) {
            $da = DatabaseAdmin::create();
            return $da->handleRequest($request);
        } else {
            $renderer = DebugView::create();
            echo $renderer->renderHeader();
            echo $renderer->renderInfo("Environment Builder", Director::absoluteBaseURL());
            echo "<div class=\"build\">";

            $da = DatabaseAdmin::create();
            $response = $da->handleRequest($request);

            echo "</div>";
            echo $renderer->renderFooter();

            return $response;
        }
    }

    public function canInit(): bool
    {
        return (
            Director::isDev()
            // We need to ensure that DevelopmentAdminTest can simulate permission failures when running
            // "dev/tasks" from CLI.
            || (Director::is_cli() && DevelopmentAdmin::config()->get('allow_all_cli'))
            || Permission::check(static::config()->get('init_permissions'))
        );
    }
    
    public function providePermissions(): array
    {
        return [
            'CAN_DEV_BUILD' => [
                'name' => _t(__CLASS__ . '.CAN_DEV_BUILD_DESCRIPTION', 'Can execute /dev/build'),
                'help' => _t(__CLASS__ . '.CAN_DEV_BUILD_HELP', 'Can execute the build command (/dev/build).'),
                'category' => DevelopmentAdmin::permissionsCategory(),
                'sort' => 100
            ],
        ];
    }
}
