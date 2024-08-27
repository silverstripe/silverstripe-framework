<?php

namespace SilverStripe\PolyExecution;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injectable;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Input that populates options from an HTTPRequest
 *
 * Use this for inputs to PolyCommand when called from a web request.
 */
class HttpRequestInput extends ArrayInput
{
    use Injectable;

    protected bool $interactive = false;

    /**
     * @param array<InputOption> $commandOptions Any options that apply for the command itself.
     * Do not include global options (e.g. flush) - they are added explicitly in the constructor.
     */
    public function __construct(HTTPRequest $request, array $commandOptions = [])
    {
        $definition = new InputDefinition([
            // Also add global options that are applicable for HTTP requests
            new InputOption('quiet', null, InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('verbose', null, InputOption::VALUE_OPTIONAL, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            // The actual flushing already happened before this point, but we still need
            // to declare the option in case someone's checking against it
            new InputOption('flush', null, InputOption::VALUE_NONE, 'Flush the cache before running the command'),
            ...$commandOptions
        ]);
        $optionValues = $this->getOptionValuesFromRequest($request, $definition);
        parent::__construct($optionValues, $definition);
    }

    /**
     * Get the verbosity that should be used based on the request vars.
     * This is used to set the verbosity for PolyOutput.
     */
    public function getVerbosity(): int
    {
        if ($this->getOption('quiet')) {
            return OutputInterface::VERBOSITY_QUIET;
        }
        $verbose = $this->getOption('verbose');
        if ($verbose === '1' || $verbose === 1 || $verbose === true) {
            return OutputInterface::VERBOSITY_VERBOSE;
        }
        if ($verbose === '2' || $verbose === 2) {
            return OutputInterface::VERBOSITY_VERY_VERBOSE;
        }
        if ($verbose === '3' || $verbose === 3) {
            return OutputInterface::VERBOSITY_DEBUG;
        }
        return OutputInterface::VERBOSITY_NORMAL;
    }

    private function getOptionValuesFromRequest(HTTPRequest $request, InputDefinition $definition): array
    {
        $options = [];
        foreach ($definition->getOptions() as $option) {
            // We'll check for the long name and all shortcuts.
            // Note the `--` and `-` prefixes are already stripped at this point.
            $candidateParams = [$option->getName()];
            $shortcutString = $option->getShortcut();
            if ($shortcutString !== null) {
                $shortcuts = explode('|', $shortcutString);
                foreach ($shortcuts as $shortcut) {
                    $candidateParams[] = $shortcut;
                }
            }
            // Get a value if there is one
            $value = null;
            foreach ($candidateParams as $candidateParam) {
                $value = $request->requestVar($candidateParam);
            }
            $default = $option->getDefault();
            // Set correct default value
            if ($value === null && $default !== null) {
                $value = $default;
            }
            // Ignore missing values if values aren't required
            if (($value === null || $value === []) && $option->isValueRequired()) {
                continue;
            }
            // Convert value to array if it should be one
            if ($value !== null && $option->isArray() && !is_array($value)) {
                $value = [$value];
            }
            // If there's a value (or the option accepts one and didn't get one), set the option.
            if ($value !== null || $option->acceptValue()) {
                // If the option doesn't accept a value, determine the correct boolean state for it.
                // If we weren't able to determine if the value's boolean-ness, default to truthy=true
                // because that's what you'd end up with with `if ($request->requestVar('myVar'))`
                if (!$option->acceptValue()) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
                }
                // We need to prefix with `--` so the superclass knows it's an
                // option rather than an argument.
                $options['--' . $option->getName()] = $value;
            }
        }
        return $options;
    }
}
