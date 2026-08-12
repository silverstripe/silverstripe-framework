<?php

namespace SilverStripe\Control\SessionHandler;

use SilverStripe\Control\Session;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\PolyExecution\PolyOutput;

/**
 * Builds the table for DatabaseSessionHandler if that is the configured session save handler.
 */
class DbBuildSessionExtension extends Extension
{
    /**
     * This extension hook is on TestSessionEnvironment, which is used by behat but not by phpunit.
     * For whatever reason, behat doesn't build the db the normal way, so we can't rely on the below
     * onAfterBuild being run in that scenario.
     */
    protected function onAfterStartTestSession(): void
    {
        $sessionHandler = $this->getSessionHandler();
        if (!$sessionHandler) {
            return;
        }

        $output = PolyOutput::create(
            Environment::isCli() ? PolyOutput::FORMAT_ANSI : PolyOutput::FORMAT_HTML,
            PolyOutput::VERBOSITY_QUIET
        );
        $output->startList();
        $sessionHandler->requireTable();
        $output->stopList();
    }

    /**
     * This extension hook is in DbBuild::doBuild(), after building the database.
     */
    protected function onAfterBuild(PolyOutput $output): void
    {
        $sessionHandler = $this->getSessionHandler();
        if (!$sessionHandler) {
            return;
        }

        $output->writeln('<options=bold>Creating table for session data</>');
        $output->startList();
        $sessionHandler->requireTable();
        $output->stopList();
        $output->writeln(['<options=bold>session database build completed!</>', '']);
    }

    private function getSessionHandler(): ?DatabaseSessionHandler
    {
        $sessionHandler = Session::getSaveHandler();
        if ($sessionHandler === null) {
            return null;
        }
        if (!is_a($sessionHandler, DatabaseSessionHandler::class)) {
            return null;
        }
        return $sessionHandler;
    }
}
