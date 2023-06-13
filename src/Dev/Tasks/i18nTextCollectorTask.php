<?php

namespace SilverStripe\Dev\Tasks;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\BuildTask;
use SilverStripe\i18n\TextCollection\i18nTextCollector;

/**
 * Collects i18n strings
 */
class i18nTextCollectorTask extends BuildTask
{

    private static $segment = 'i18nTextCollectorTask';

    protected $title = "i18n Textcollector Task";

    protected $description = "
		Traverses through files in order to collect the 'entity master tables'
		stored in each module.

		Parameters:
		- locale: Sets default locale
		- writer: Custom writer class (defaults to i18nTextCollector_Writer_RailsYaml)
		- module: One or more modules to limit collection (comma-separated)
		- merge: Merge new strings with existing ones already defined in language files (default: TRUE)
	";

    /**
     * This is the main method to build the master string tables with the original strings.
     * It will search for existent modules that use the i18n feature, parse the _t() calls
     * and write the resultant files in the lang folder of each module.
     *
     * @uses DataObject::collectI18nStatics()
     *
     * @param HTTPRequest $request
     */
    public function run($request)
    {
        Environment::increaseTimeLimitTo();
        $collector = i18nTextCollector::create($request->getVar('locale'));

        $merge = $this->getIsMerge($request);

        // Custom writer
        $writerName = $request->getVar('writer');
        if ($writerName) {
            $writer = Injector::inst()->get($writerName);
            $collector->setWriter($writer);
        }

        // Get restrictions
        $restrictModules = ($request->getVar('module'))
            ? explode(',', $request->getVar('module'))
            : null;

        $collector->run($restrictModules, $merge);

        Debug::message(__CLASS__ . " completed!", false);
    }

    /**
     * Check if we should merge
     *
     * @param HTTPRequest $request
     * @return bool
     */
    protected function getIsMerge($request)
    {
        $merge = $request->getVar('merge');

        // Default to true if not given
        if (!isset($merge)) {
            return true;
        }

        // merge=0 or merge=false will disable merge
        return !in_array($merge, ['0', 'false']);
    }
}
