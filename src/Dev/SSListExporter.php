<?php

namespace SilverStripe\Dev;


use SebastianBergmann\Exporter\Exporter;
use SebastianBergmann\RecursionContext\Context;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;

class SSListExporter extends Exporter
{

    protected function recursiveExport(&$value, $indentation, $processed = null)
    {
        if (!$processed) {
            $processed = new Context;
        }

        $whitespace = str_repeat(' ', 4 * $indentation);

        if ($value instanceof SS_List) {
            $className = get_class($value);
            if (($key = $processed->contains($value)) !== false) {
                return $className . ' &' . $key;
            }

            $list  = $value;
            $key    = $processed->add($value);
            $values = '';

            if ($list->count() > 0) {
                foreach ($list as $k => $v) {
                    $values .= sprintf(
                        '%s    %s ' . "\n",
                        $whitespace,
                        $this->recursiveExport($v, $indentation)
                    );
                }

                $values = "\n" . $values . $whitespace;
            }

            return sprintf($className . ' &%s (%s)', $key, $values);
        }

        if ($value instanceof ViewableData) {
            $className = get_class($value);
            $data = $this->toMap($value);

            return sprintf(
                '%s    %s => %s' . "\n",
                $whitespace,
                $className,
                $this->recursiveExport($data, $indentation + 2, $processed)
            );
        }


        return parent::recursiveExport($value, $indentation, $processed);
    }

    /**
     * @param ViewableData $object
     * @return array
     */
    public function toMap(ViewableData $object)
    {
        return $object->hasMethod('toMap')
            ? $object->toMap()
            : [];
    }
}
