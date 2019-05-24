<?php
namespace SilverStripe\Core\Tests\Injector\MethodInjectorTest;

use SilverStripe\Dev\TestOnly;

class InjectableConstructorTagged extends InjectableConstructor implements TestOnly
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
     * @Inject(TestDependency,testTag)
     */
    public function __construct(TestDependency $protectedDependency, ...$additionalParams)
    {
        parent::__construct($protectedDependency, ...$additionalParams);
    }
}
