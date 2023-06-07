<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

/**
 * Interface for a generic build task. Does not support dependencies. This will simply
 * run a chunk of code when called.
 *
 * To disable the task (in the case of potentially destructive updates or deletes), declare
 * the $Disabled property on the subclass.
 */
abstract class BuildTask
{
    use Injectable;
    use Configurable;
    use Extensible;

    public function __construct()
    {
    }

    /**
     * Set a custom url segment (to follow dev/tasks/)
     *
     * @config
     * @var string
     */
    private static $segment = null;

    /**
     * change this to `bool` in CMS6
     * @var bool|null
     */
    private static ?bool $is_enabled = null;

    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     * @deprecated - remove in CMS6
     */
    protected $enabled = true;

    /**
     * @var string $title Shown in the overview on the {@link TaskRunner}
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title;

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = 'No description available';

    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param HTTPRequest $request
     * @return void
     */
    abstract public function run($request);

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        $isEnabled = $this->config()->get('enabled');
        $isDefaultEnabled = Config::inst()->get(__CLASS__, 'default_enabled');

        if ($isEnabled === null) {
            Deprecation::notice('6.0', 'Null values not allowed in CMS6 ...');

            return static::$enabled ?? $isDefaultEnabled;
        }
        return $isEnabled;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title ?: static::class;
    }

    /**
     * @return string HTML formatted description
     */
    public function getDescription()
    {
        return $this->description;
    }
}
