<?php

namespace SilverStripe\Core\Tests\ClassInfoTest;

#[ClassAttribute1('Test1')]
#[ClassAttribute2('Test2')]
#[ClassAttribute3('Test3')]
class ClassWithAttributes1
{
    #[PropertyAttribute1('Test1')]
    #[PropertyAttribute2('Test2')]
    public string $prop1 = 'test1234';

    #[PropertyAttribute1('Test3')]
    protected string $prop2 = 'test1234';

    #[PropertyAttribute3('Test4')]
    protected string $prop3 = 'test1234';

    #[MethodAttribute1('Test1')]
    #[MethodAttribute2('Test2')]
    public function firstMethod(): void
    {
    }

    #[MethodAttribute1('Test3')]
    protected function secondMethod(): void
    {
    }

    #[MethodAttribute3('Test4')]
    protected function thirdMethod(): void
    {
    }
}
