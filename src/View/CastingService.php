<?php

namespace SilverStripe\View;

use LogicException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\ModelData;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBText;

class CastingService
{
    use Injectable;

    /**
     * Cast a value to the relevant object (usually a DBField instance) for use in the view layer.
     *
     * @param ModelData|array|null $source Where the data originates from. This is used both to check for casting helpers
     * and to help set the value in cast DBField instances.
     * @param bool $strict If true, an object will be returned even if $data is null.
     */
    public function cast(mixed $data, ModelData|array|null $source = null, string $fieldName = '', bool $strict = false): ?object
    {
        // Assume anything that's an object is intentionally using whatever class it's using
        // and don't cast it.
        if (is_object($data)) {
            return $data;
        }

        // null is null - we shouldn't cast it to an object, because that makes it harder
        // for downstream checks to know there's "no value".
        if (!$strict && $data === null) {
            return null;
        }

        $serviceKey = null;
        if ($source instanceof ModelData) {
            $serviceKey = $source->castingHelper($fieldName);
        }

        // Cast to object if there's an explicit casting for this field
        // Explicit casts take precedence over array casting
        if ($serviceKey) {
            $castObject = Injector::inst()->create($serviceKey, $fieldName);
            if (!ClassInfo::hasMethod($castObject, 'setValue')) {
                throw new LogicException('Explicit casting service must have a setValue method.');
            }
            $castObject->setValue($data, $source);
            return $castObject;
        }

        // Wrap arrays in ModelData so templates can handle them
        if (is_array($data)) {
            return array_is_list($data) ? ArrayList::create($data) : ArrayData::create($data);
        }

        // Fall back to default casting
        $serviceKey = $this->getDefaultServiceKey($data, $source, $fieldName);
        $castObject = Injector::inst()->create($serviceKey, $fieldName);
        if (!ClassInfo::hasMethod($castObject, 'setValue')) {
            throw new LogicException('Default service must have a setValue method.');
        }
        $castObject->setValue($data, $source);
        return $castObject;
    }

    /**
     * Get the default service to use if no explicit service is declared for this field on the source model.
     */
    private function getDefaultServiceKey(mixed $data, mixed $source = null, string $fieldName = ''): ?string
    {
        $default = null;
        if ($source instanceof ModelData) {
            $default = $source::config()->get('default_cast');
            if ($default === null) {
                $failover = $source->getFailover();
                if ($failover) {
                    $default = $this->getDefaultServiceKey($data, $failover, $fieldName);
                }
            }
        }
        if ($default !== null) {
            return $default;
        }

        return match (gettype($data)) {
            'boolean' => DBBoolean::class,
            'string' => DBText::class,
            'double' => DBFloat::class,
            'integer' => DBInt::class,
            default => DBText::class,
        };
    }
}
