<?php

namespace SilverStripe\Model;

use ReflectionObject;

/**
 * Allows you to render debug information about a {@link ModelData} object into a template.
 */
class ModelDataDebugger extends ModelData
{
    protected ModelData $object;

    public function __construct(ModelData $object)
    {
        $this->object = $object;
        parent::__construct();
    }

    /**
     * Returns the rendered debugger
     */
    public function __toString(): string
    {
        return (string)$this->forTemplate();
    }

    /**
     * Return debugging information, as XHTML. If a field name is passed, it will show debugging information on that
     * field, otherwise it will show information on all methods and fields.
     */
    public function forTemplate(?string $field = null): string
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
            $debug .= ModelDataDebugger::create($this->object->data())->forTemplate();
        }

        return $debug;
    }
}
