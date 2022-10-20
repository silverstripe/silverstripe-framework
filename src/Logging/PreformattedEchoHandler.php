<?php

namespace SilverStripe\Logging;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

/**
 * Echo the output as preformatted HTML, emulating console output in a browser.
 * Tiding us over until we can properly decoupled web from CLI output.
 * Do not use this API outside of core modules,
 * it'll likely be removed as part of a larger refactor.
 *
 * See https://github.com/silverstripe/silverstripe-framework/issues/5542
 *
 * @internal
 */
class PreformattedEchoHandler extends AbstractProcessingHandler
{

    protected function write(LogRecord $record): void
    {
        echo sprintf('<pre>%s</pre>', htmlspecialchars($record['formatted'], ENT_QUOTES, 'UTF-8'));
    }
}
