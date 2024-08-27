<?php

namespace SilverStripe\Dev\Tasks;

use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\i18n\TextCollection\i18nTextCollector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Collects i18n strings
 *
 * It will search for existent modules that use the i18n feature, parse the _t() calls
 * and write the resultant files in the lang folder of each module.
 */
class i18nTextCollectorTask extends BuildTask
{
    protected static string $commandName = 'i18nTextCollectorTask';

    protected string $title = "i18n Textcollector Task";

    protected static string $description = 'Traverses through files in order to collect the '
                                            . '"entity master tables" stored in each module.';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        Environment::increaseTimeLimitTo();
        $collector = i18nTextCollector::create($input->getOption('locale'));

        $merge = $this->getIsMerge($input);

        // Custom writer
        $writerName = $input->getOption('writer');
        if ($writerName) {
            $writer = Injector::inst()->get($writerName);
            $collector->setWriter($writer);
        }

        // Get restrictions
        $restrictModules = ($input->getOption('module'))
            ? explode(',', $input->getOption('module'))
            : null;

        $collector->run($restrictModules, $merge);

        return Command::SUCCESS;
    }

    /**
     * Check if we should merge
     */
    protected function getIsMerge(InputInterface $input): bool
    {
        $merge = $input->getOption('merge');
        // merge=0 or merge=false will disable merge
        return !in_array($merge, ['0', 'false']);
    }

    public function getOptions(): array
    {
        return [
            new InputOption('locale', null, InputOption::VALUE_REQUIRED, 'Sets default locale'),
            new InputOption('writer', null, InputOption::VALUE_REQUIRED, 'Custom writer class (must implement the <info>SilverStripe\i18n\Messages\Writer</> interface)'),
            new InputOption('module', null, InputOption::VALUE_REQUIRED, 'One or more modules to limit collection (comma-separated)'),
            new InputOption('merge', null, InputOption::VALUE_NEGATABLE, 'Merge new strings with existing ones already defined in language files', true),
        ];
    }
}
