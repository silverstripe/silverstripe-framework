<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Assets\File;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Dev\Debug;

/**
 * Secures requests by only allowing a whitelist of Host values
 */
class ExecMetricMiddleware implements HTTPMiddleware
{
    /**
     * @inheritdoc
     */
    public function process(HTTPRequest $request, callable $delegate)
    {
        if (!$this->showMetric($request)) {
            return $delegate($request);
        }

        $start = microtime(true);
        try {
            return $delegate($request);
        } finally {
            $end = microtime(true);
            Debug::message(
                sprintf(
                    "Execution time: %s, Peak memory usage: %s\n",
                    $this->formatExecutionTime($start, $end),
                    $this->formatPeakMemoryUsage()
                ),
                false
            );
        }
    }

    private function showMetric(HTTPRequest $request)
    {
        return Director::isDev() && array_key_exists('execmetric', $request->getVars());
    }

    /**
     * Convert the provided start and end time to a interval in secs.
     * @param float $start
     * @param float $end
     * @return string
     */
    private function formatExecutionTime($start, $end)
    {
        $diff = round($end - $start, 4);
        return $diff . ' seconds';
    }

    /**
     * Get the peak memory usage formatted has a string and a meaningful unit.
     * @return string
     */
    private function formatPeakMemoryUsage()
    {
        $bytes = memory_get_peak_usage(true);
        return File::format_size($bytes);
    }
}
