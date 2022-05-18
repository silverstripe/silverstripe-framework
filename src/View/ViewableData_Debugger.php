<?php

namespace SilverStripe\View;

use ReflectionObject;

/**
 * Allows you to render debug information about a {@link ViewableData} object into a template.
 */
class ViewableData_Debugger extends ViewableData
{

    /**
     * @var ViewableData
     */
    protected $object;

    /**
     * @param ViewableData $object
     */
    public function __construct(ViewableData $object)
    {
        $this->object = $object;
        parent::__construct();
    }

    /**
     * @return string The rendered debugger
     */
    public function __toString()
    {
        return (string)$this->forTemplate();
    }

    /**
     * Return debugging information, as XHTML. If a field name is passed, it will show debugging information on that
     * field, otherwise it will show information on all methods and fields.
     *
     * @param string $field the field name
     * @return string
     */
    public function forTemplate($field = null)
    {
        // debugging info for a specific field
        $class = get_class($this->object);
        if ($field) {
            return "<b>Debugging Information for {$class}->{$field}</b><br/>" . ($this->object->hasMethod($field) ? "Has method '$field'<br/>" : null) . ($this->object->hasField($field) ? "Has field '$field'<br/>" : null);
        }

        // debugging information for the entire class
        $reflector = new ReflectionObject($this->object);
        $debug = "<b>Debugging Information: all methods available in '{$class}'</b><br/><ul>";
        foreach ($this->object->allMethodNames() as $method) {
            if ($method[0] !== '_') {
                if ($reflector->hasMethod($method) && $method = $reflector->getMethod($method)) {
                    if ($method->isPublic()) {
                        $debug .= "<li>\${$method->getName()}";

                        if (count($method->getParameters() ?? [])) {
                            $debug .= ' <small>(' . implode(', ', $method->getParameters()) . ')</small>';
                        }

                        $debug .= '</li>';
                    }
                } else {
                    $debug .= "<li>\$$method</li>";
                }
            }
        }

        $debug .= '</ul>';

        if ($this->object->hasMethod('toMap')) {
            $debug .= "<b>Debugging Information: all fields available in '{$class}'</b><br/><ul>";

            foreach ($this->object->toMap() as $field => $value) {
                $debug .= "<li>\$$field</li>";
            }

            $debug .= "</ul>";
        }

        // check for an extra attached data
        if ($this->object->hasMethod('data') && $this->object->data() != $this->object) {
            $debug .= ViewableData_Debugger::create($this->object->data())->forTemplate();
        }

        return $debug;
    }
}
