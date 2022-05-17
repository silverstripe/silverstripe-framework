<?php

namespace SilverStripe\View;

use Exception;

/**
 * This is the exception raised when failing to parse a template. Note that we don't currently do any static analysis,
 * so we can't know if the template will run, just if it's malformed. It also won't catch mistakes that still look
 * valid.
 */
class SSTemplateParseException extends Exception
{

    /**
     * SSTemplateParseException constructor.
     * @param string $message
     * @param SSTemplateParser $parser
     */
    public function __construct($message, $parser)
    {
        $prior = substr($parser->string ?? '', 0, $parser->pos);

        preg_match_all('/\r\n|\r|\n/', $prior ?? '', $matches);
        $line = count($matches[0] ?? []) + 1;

        parent::__construct("Parse error in template on line $line. Error was: $message");
    }
}
