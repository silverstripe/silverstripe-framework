<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\ArrayList;

/**
 * Encapsulates a collection of components following the
 * {@link GridFieldComponent} interface. While the {@link GridField} itself
 * has some configuration in the form of setters, most of the details are
 * dealt with through components.
 *
 * For example, you would add a {@link GridFieldPaginator} component to enable
 * pagination on the listed records, and configure it through
 * {@link GridFieldPaginator->setItemsPerPage()}.
 *
 * In order to reduce the amount of custom code required, the framework
 * provides some default configurations for common use cases:
 *
 * - {@link GridFieldConfig_Base} (added by default to GridField)
 * - {@link GridFieldConfig_RecordEditor}
 * - {@link GridFieldConfig_RelationEditor}
 */
class GridFieldConfig
{
    use Injectable;
    use Extensible;
    use Configurable;

    /**
     * @var ArrayList<GridFieldComponent>
     */
    protected $components = null;

    public function __construct()
    {
        $this->components = new ArrayList();
    }

    /**
     * @param GridFieldComponent $component
     * @param string $insertBefore The class of the component to insert this one before
     * @return $this
     */
    public function addComponent(GridFieldComponent $component, $insertBefore = null)
    {
        if ($insertBefore) {
            $existingItems = $this->getComponents();
            $this->components = new ArrayList;
            $inserted = false;
            foreach ($existingItems as $existingItem) {
                if (!$inserted && $existingItem instanceof $insertBefore) {
                    $this->components->push($component);
                    $inserted = true;
                }
                $this->components->push($existingItem);
            }
            if (!$inserted) {
                $this->components->push($component);
            }
        } else {
            $this->getComponents()->push($component);
        }
        return $this;
    }

    /**
     * @param GridFieldComponent|GridFieldComponent[] ...$component One or more components, or an array of components
     * @return $this
     */
    public function addComponents($component = null)
    {
        $components = is_array($component) ? $component : func_get_args();
        foreach ($components as $component) {
            $this->addComponent($component);
        }
        return $this;
    }

    /**
     * @param GridFieldComponent $component
     * @return $this
     */
    public function removeComponent(GridFieldComponent $component)
    {
        $this->getComponents()->remove($component);
        return $this;
    }

    /**
     * @param string|string[] $types Class name or interface, or an array of the same
     * @return $this
     */
    public function removeComponentsByType($types)
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        foreach ($types as $type) {
            $components = $this->getComponentsByType($type);
            foreach ($components as $component) {
                $this->removeComponent($component);
            }
        }

        return $this;
    }

    /**
     * @return ArrayList<GridFieldComponent>
     */
    public function getComponents()
    {
        if (!$this->components) {
            $this->components = new ArrayList();
        }
        return $this->components;
    }

    /**
     * Returns all components extending a certain class, or implementing a certain interface.
     *
     * @template T of GridFieldComponent
     * @param class-string<T> $type Class name or interface
     * @return ArrayList<T>
     */
    public function getComponentsByType($type)
    {
        $components = new ArrayList();
        foreach ($this->components as $component) {
            if ($component instanceof $type) {
                $components->push($component);
            }
        }
        return $components;
    }

    /**
     * Returns the first available component with the given class or interface.
     *
     * @template T of GridFieldComponent
     * @param class-string<T> $type ClassName
     * @return T|null
     */
    public function getComponentByType($type)
    {
        foreach ($this->components as $component) {
            if ($component instanceof $type) {
                return $component;
            }
        }
        return null;
    }
}
