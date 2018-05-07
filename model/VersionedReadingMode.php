<?php

/**
 * Converter helpers for versioned args
 */
class VersionedReadingMode
{
    /**
     * Convert reading mode string to dataquery params.
     * Only supports stage / archive
     *
     * @param string $mode Reading mode string
     * @return array|null
     */
    public static function toDataQueryParams($mode)
    {
        if (empty($mode)) {
            return null;
        }
        if (!is_string($mode)) {
            throw new InvalidArgumentException("mode must be a string");
        }
        $parts = explode('.', $mode);
        switch ($parts[0]) {
            case 'Archive':
                return array(
                    'Versioned.mode' => 'archive',
                    'Versioned.date' => $parts[1],
				);
            case 'Stage':
                self::validateStage($parts[1]);
                return array(
                    'Versioned.mode' => 'stage',
                    'Versioned.stage' => $parts[1],
				);
            default:
                // Unsupported mode
                return null;
        }
    }

    /**
     * Converts dataquery params to original reading mode.
     * Only supports stage / archive
     *
     * @param array $params
     * @return string|null
     */
    public static function fromDataQueryParams($params)
    {
        // Switch on reading mode
        if (empty($params["Versioned.mode"])) {
            return null;
        }

        switch ($params["Versioned.mode"]) {
            case 'archive':
                return 'Archive.' . $params['Versioned.date'];
            case 'stage':
                return 'Stage.' . $params['Versioned.stage'];
            default:
                return null;
        }
    }

    /**
     * Convert querystring arguments to reading mode.
     * Only supports stage / archive mode
     *
     * @param array|string $query Querystring arguments (array or string)
     * @return string|null Reading mode, or null if not found / supported
     */
    public static function fromQueryString($query)
    {
        if (is_string($query)) {
            parse_str($query, $query);
        }
        if (empty($query)) {
            return null;
        }

        // Archive date is specified
		if (isset($query['archiveDate']) && strtotime($query['archiveDate'])) {
            return 'Archive.' . $query['archiveDate'];
        }

        // Stage is specified by itself
        if (isset($query['stage']) && strcasecmp($query['stage'], Versioned::DRAFT) === 0) {
            return 'Stage.' . Versioned::DRAFT;
        }
        if (isset($query['stage']) && strcasecmp($query['stage'], Versioned::LIVE) === 0) {
            return 'Stage.' . Versioned::LIVE;
        }

        // Unsupported query mode
        return null;
    }

    /**
     * Build querystring arguments for current reading mode.
     * Supports stage / archive only.
     *
     * @param string $mode
     * @return array List of querystring arguments as an array
     */
    public static function toQueryString($mode)
    {
        if (empty($mode)) {
            return null;
        }
        if (!is_string($mode)) {
            throw new InvalidArgumentException("mode must be a string");
        }
        $parts = explode('.', $mode);
        switch ($parts[0]) {
            case 'Archive':
                return array(
                    'archiveDate' => $parts[1],
				);
            case 'Stage':
                self::validateStage($parts[1]);
                return array(
                    'stage' => $parts[1],
				);
            default:
                // Unsupported mode
                return null;
        }
    }

    /**
     * Validate the stage is valid, throwing an exception if it's not
     *
     * @param string $stage
     */
    public static function validateStage($stage)
    {
    	// Any stage is allowed in 3.x. Note that 4.x only allows Stage / Live
		// Any string that contains no dots is ok.
		if (empty($stage) || !preg_match('/^([^.]+)$/', $stage)) {
            throw new InvalidArgumentException("Invalid stage name \"{$stage}\"");
        }
    }
}
