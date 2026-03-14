<?php

namespace SilverStripe\View\Exception;

use LogicException;

/**
 * Exception that indicates a template was not found when attempting to use a template engine
 */
class MissingTemplateException extends LogicException
{
}
