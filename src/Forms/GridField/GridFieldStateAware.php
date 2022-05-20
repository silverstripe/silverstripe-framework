<?php


namespace SilverStripe\Forms\GridField;

use SilverStripe\Core\Injector\Injector;

/**
 * A trait that makes a class able to consume and use a {@link GridFieldStateManagerInterface}
 * implementation
 */
trait GridFieldStateAware
{
    /**
     * @var GridFieldStateManagerInterface
     */
    protected $stateManager;

    /**
     * @param GridFieldStateManagerInterface $manager
     * @return self
     */
    public function setStateManager(GridFieldStateManagerInterface $manager): self
    {
        $this->stateManager = $manager;

        return $this;
    }

    /**
     * Fallback on the direct Injector access, but allow a custom implementation
     * to be applied
     *
     * @return GridFieldStateManagerInterface
     */
    public function getStateManager(): GridFieldStateManagerInterface
    {
        return $this->stateManager ?: Injector::inst()->get(GridFieldStateManagerInterface::class);
    }
}
