<?php

namespace SilverStripe\Control\Middleware;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * ExpressionBased Middleware.
 */
class ExpressionBasedMiddleware implements HTTPMiddleware
{
    public $expressionLanguage;

    public static $dependencies = [
        'expressionLanguage' => '%$'.ExpressionLanguage::class,
    ];

    protected $context;

    protected $expressions;

    public function __construct(array $context = [])
    {
        $this->context = $context;
    }

    /**
     * Workaround for lack of property typing in php < 7.4.
     */
    public function __set(string $name, string $value)
    {
        if (0 === strpos($name, 'req') || 0 === strpos($name, 'res')) {
            $this->expressions[substr($name, 0, 3)] = $value;
        }
    }

    public function process(HTTPRequest $request, callable $delegate)
    {
        $this->context['req'] = $request;
        if (isset($this->expressions['req'])) {
            $result = $this->expressionLanguage->evaluate($this->expressions['req'], $this->context);
            if ($result instanceof HTTPResponse) {
                return $result;
            }
        }
        $response = $delegate($request);
        if (isset($this->expressions['res'])) {
            $this->expressionLanguage->evaluate($this->expressions['res'], $this->context);
        }

        return $response;
    }
}
