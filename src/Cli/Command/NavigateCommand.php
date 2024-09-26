<?php

namespace SilverStripe\Cli\Command;

use SilverStripe\Control\CLIRequestBuilder;
use SilverStripe\Control\HTTPApplication;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Kernel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command that simulates an HTTP request to the Silverstripe App based on CLI input.
 */
#[AsCommand(name: 'navigate', description: 'Navigate to a URL on your site via a simulated HTTP request')]
class NavigateCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Convert input into HTTP request.
        // Use the kernel we already booted for consistency and performance reasons
        $app = new HTTPApplication(Injector::inst()->get(Kernel::class));
        $request = CLIRequestBuilder::createFromInput($input);

        // Handle request and output resonse body
        $response = $app->handle($request);
        $output->writeln($response->getBody(), OutputInterface::OUTPUT_RAW);

        // Transform HTTP status code into sensible exit code
        $responseCode = $response->getStatusCode();
        $output->writeln("<options=bold>RESPONSE STATUS CODE WAS {$responseCode}</>", OutputInterface::VERBOSITY_VERBOSE);
        // We can't use the response code for unsuccessful requests directly as the exit code
        // because symfony gives us an exit code ceiling of 255. So just use the regular constants.
        return match (true) {
            ($responseCode >= 200 && $responseCode < 400) => Command::SUCCESS,
            ($responseCode >= 400 && $responseCode < 500) => Command::INVALID,
            default => Command::FAILURE,
        };
    }

    protected function configure(): void
    {
        $this->setHelp(<<<HELP
        Use verbose mode to see the HTTP response status code.
        The <info>get-vars</> arg can either be separated GET variables, or a full query string
          e.g: <comment>sake navigate about-us/team q=test arrayval[]=value1 arrayval[]=value2</>
          e.g: <comment>sake navigate about-us/team q=test<info>&</info>arrayval[]=value1<info>&</info>arrayval[]=value2</>
        HELP);
        $this->addArgument(
            'path',
            InputArgument::REQUIRED,
            'Relative path to navigate to (e.g: <info>about-us/team</>). Can optionally start with a "/"'
        );
        $this->addArgument(
            'get-vars',
            InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
            'Optional GET variables or a query string'
        );
    }
}
