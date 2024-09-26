<?php

namespace SilverStripe\View\Exception;

use LogicException;

/**
 * Exception that indicates a template was not found when attemping to use a template engine
 */
class MissingTemplateException extends LogicException
{
}
