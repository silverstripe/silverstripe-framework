<?php

namespace SilverStripe\Cli;

use SilverStripe\Dev\Deprecation;
use SilverStripe\Core\ArrayLib;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * Represents an input coming from the CLI arguments - but converts legacy arg-style parameters to flags.
 *
 * e.g. `ddev dev:build flush=1` is converted to `ddev dev:build --flush`.
 * Doesn't convert anything that isn't explicitly an InputOption in the relevant InputDefinition.
 * Removes the parameters from the input args (e.g. doesn't become `ddev dev:build flush=1 --flush`).
 *
 * @deprecated 6.0.0 Use Symfony\Component\Console\Input\ArgvInput instead.
 */
class LegacyParamArgvInput extends ArgvInput
{
    /**
     * Input from the command line.
     *
     * We need a separate copy of this because the one held by the parent class is private
     * and not exposed until symfony/console 7.1
     */
    private array $argv;

    public function __construct(?array $argv = null, ?InputDefinition $definition = null)
    {
        Deprecation::withSuppressedNotice(
            fn() => Deprecation::notice('6.0.0', 'Use ' . ArgvInput::class . ' instead', Deprecation::SCOPE_CLASS)
        );
        $argv ??= $_SERVER['argv'] ?? [];
        parent::__construct($argv, $definition);
        // Strip the application name, matching what the parent class did with its copy
        array_shift($argv);
        $this->argv = $argv;
    }

    public function hasParameterOption(string|array $values, bool $onlyParams = false): bool
    {
        if (parent::hasParameterOption($values, $onlyParams)) {
            return true;
        }
        return $this->hasLegacyParameterOption($values);
    }

    public function getParameterOption(string|array $values, string|bool|int|float|array|null $default = false, bool $onlyParams = false): mixed
    {
        if (parent::hasParameterOption($values, $onlyParams)) {
            return parent::getParameterOption($values, $default, $onlyParams);
        }
        return $this->getLegacyParameterOption($values, $default);
    }

    /**
     * Binds the current Input instance with the given arguments and options.
     *
     * Also converts any arg-style params into true flags, based on the options defined.
     */
    public function bind(InputDefinition $definition): void
    {
        // Convert arg-style params into flags
        $tokens = $this->argv;
        $convertedFlags = [];
        $hadLegacyParams = false;
        foreach ($definition->getOptions() as $option) {
            $flagName = '--' . $option->getName();
            // Check if there is a legacy param first. This saves us from accidentally getting
            // values that come after the end of options (--) signal
            if (!$this->hasLegacyParameterOption($flagName)) {
                continue;
            }
            // Get the value from the legacy param
            $value = $this->getLegacyParameterOption($flagName);
            if ($value && !$this->hasLegacyParameterOption($flagName . '=' . $value)) {
                // symfony/console will try to get the value from the next argument if the current argument ends with `=`
                // We don't want to count that as the value, so double check it.
                $value = null;
            } elseif ($option->acceptValue()) {
                if ($value === '' || $value === null) {
                    $convertedFlags[] = $flagName;
                } else {
                    $convertedFlags[] = $flagName . '=' . $value;
                }
            } else {
                // If the option doesn't accept a value, only add the flag if the value is true.
                $valueAsBool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true;
                if ($valueAsBool) {
                    $convertedFlags[] = $flagName;
                }
            }
            $hadLegacyParams = true;
            // Remove the legacy param from the token set
            foreach ($tokens as $i => $token) {
                if (str_starts_with($token, $option->getName() . '=')) {
                    unset($tokens[$i]);
                    break;
                }
            }
        }
        if (!empty($convertedFlags)) {
            // Make sure it's before the end of options (--) signal if there is one.
            $tokens = ArrayLib::insertBefore($tokens, $convertedFlags, '--', true, true);
        }
        if ($hadLegacyParams) {
            // We only want the "notice" once regardless of how many params there are.
            Deprecation::notice(
                '6.0.0',
                'Using `param=value` style flags is deprecated. Use `--flag=value` CLI flags instead.',
                Deprecation::SCOPE_GLOBAL
            );
            // Set the new tokens so the parent class can operate on them.
            // Specifically skip setting $this->argv in case someone decides to bind to a different
            // input definition afterwards for whatever reason.
            parent::setTokens($tokens);
        }
        parent::bind($definition);
    }

    protected function setTokens(array $tokens): void
    {
        $this->argv = $tokens;
        parent::setTokens($tokens);
    }

    private function hasLegacyParameterOption(string|array $values): bool
    {
        $values = $this->getLegacyParamsForFlags((array) $values);
        if (empty($values)) {
            return false;
        }
        return parent::hasParameterOption($values, true);
    }

    public function getLegacyParameterOption(string|array $values, string|bool|int|float|array|null $default = false): mixed
    {
        $values = $this->getLegacyParamsForFlags((array) $values);
        if (empty($values)) {
            return $default;
        }
        return parent::getParameterOption($values, $default, true);
    }

    /**
     * Given a set of flag names, return what they would be called in the legacy format.
     */
    private function getLegacyParamsForFlags(array $flags): array
    {
        $legacyParams = [];
        foreach ($flags as $flag) {
            // Only allow full flags e.g. `--flush`, not shortcuts like `-f`
            if (!str_starts_with($flag, '--')) {
                continue;
            }
            // Convert to legacy format, e.g. `--flush` becomes `flush=`
            // but if there's already an equals e.g. `--flush=1` keep it (`flush=1`)
            // because the developer is checking for a specific value set to the flag.
            $flag = ltrim($flag, '-');
            if (!str_contains($flag, '=')) {
                $flag .= '=';
            }
            $legacyParams[] = $flag;
        }
        return $legacyParams;
    }
}
