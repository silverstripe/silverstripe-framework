<?php
namespace SilverStripe\Core\Tests\Injector\MethodInjectorTest;

use SilverStripe\Dev\TestOnly;

class InjectableConstructor implements TestOnly
{
    /**
     * @var TestDependency
     */
    protected $protectedDependency;

    /**
     * @var array
     */
    protected $additionalParams;

    /**
     * @param TestDependency $protectedDependency
     * @param array $additionalParams
     * @Injectable
     */
    public function __construct(TestDependency $protectedDependency, ...$additionalParams)
    {
        $this->protectedDependency = $protectedDependency;
        $this->additionalParams = $additionalParams;
    }

    /**
     * @return mixed
     */
    public function getProtectedDependency()
    {
        return $this->protectedDependency;
    }

    /**
     * @return array
     */
    public function getAdditionalParams()
    {
        return $this->additionalParams;
    }
}
