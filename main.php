<?php

/**
 * This file is deprecated.
 * Please setup index.php and update .htaccess in your project root instead
 */

use SilverStripe\Control\HTTPResponse;

require __DIR__ . '/src/includes/autoload.php';

// Ensure installer is available
if (is_writable(BASE_PATH)) {
    $content = <<<'PHP'
<?php
include './vendor/silverstripe/framework/src/Dev/Install/install.php';
PHP;
    file_put_contents(BASE_PATH . '/install.php', $content);
}

// Redirect to installer automatically
$response = new HTTPResponse();
$response->redirect(BASE_URL . '/install.php', 302);
$response->output();
