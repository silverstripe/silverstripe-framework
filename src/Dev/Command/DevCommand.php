<?php

namespace SilverStripe\Dev\Command;

use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Terminal;

/**
 * A command that can be run from CLI or via an HTTP request in a dev/* route
 */
abstract class DevCommand extends PolyCommand
{
    private static array $permissions_for_browser_execution = [
        'ADMIN',
        'ALL_DEV_ADMIN' => true,
    ];

    public function run(InputInterface $input, PolyOutput $output): int
    {
        $terminal = new Terminal();
        $heading = $this->getHeading();
        if ($heading) {
            // Output heading
            $underline = str_repeat('-', min($terminal->getWidth(), strlen($heading)));
            $output->writeForAnsi(["<options=bold>{$heading}</>", $underline], true);
            $output->writeForHtml("<h2>{$heading}</h2>");
        } else {
            // Only print the title in CLI (and only if there's no heading)
            // The DevAdminController outputs the title already for HTTP stuff.
            $title = $this->getTitle();
            $underline = str_repeat('-', min($terminal->getWidth(), strlen($title)));
            $output->writeForAnsi(["<options=bold>{$title}</>", $underline], true);
        }

        return $this->execute($input, $output);
    }

    /**
     * The code for running this command.
     *
     * Output should be agnostic - do not include explicit HTML in the output unless there is no API
     * on `PolyOutput` for what you want to do (in which case use the writeForHtml() method).
     *
     * Use symfony/console ANSI formatting to style the output.
     * See https://symfony.com/doc/current/console/coloring.html
     *
     * @return int 0 if everything went fine, or an exit code
     */
    abstract protected function execute(InputInterface $input, PolyOutput $output): int;

    /**
     * Content to output before command is executed.
     * In HTML format this will be an h2.
     */
    abstract protected function getHeading(): string;
}
