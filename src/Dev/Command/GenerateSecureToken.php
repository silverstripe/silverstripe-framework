<?php

namespace SilverStripe\Dev\Command;

use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Security\RandomGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command to generate a secure token.
 * Can be run either via an HTTP request or the CLI.
 */
class GenerateSecureToken extends DevCommand
{
    protected static string $commandName = 'generatesecuretoken';

    protected static string $description = 'Generate a secure token';

    public function getTitle(): string
    {
        return 'Secure token';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $token = RandomGenerator::create()->randomToken($input->getOption('algorithm'));

        $output->writeForHtml('<code>');
        $output->writeln($token);
        $output->writeForHtml('</code>');

        return Command::SUCCESS;
    }

    protected function getHeading(): string
    {
        return 'Generating new token';
    }

    public function getOptions(): array
    {
        return [
            new InputOption(
                'algorithm',
                null,
                InputOption::VALUE_REQUIRED,
                'The hashing algorithm used to generate the token. Can be any identifier listed in <href=https://www.php.net/manual/en/function.hash-algos.php>hash_algos()</>',
                'sha1',
                hash_algos()
            ),
        ];
    }
}
