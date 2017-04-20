<?php

namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Core\Manifest\ClassContentRemover;
use SilverStripe\Dev\SapphireTest;

class ClassContentRemoverTest extends SapphireTest
{
    public function testRemoveClassContent()
    {
        $filePath = dirname(__FILE__) . '/fixtures/classcontentremover/ContentRemoverTestA.php';
        $cleanContents = ClassContentRemover::remove_class_content($filePath);

        $expected = '<?php
 namespace TestNamespace\\Testing; use TestNamespace\\{Test1, Test2, Test3}; class MyTest extends Test1 implements Test2 {}';

        $this->assertEquals($expected, $cleanContents);
    }

    public function testRemoveClassContentConditional()
    {
        $filePath = dirname(__FILE__) . '/fixtures/classcontentremover/ContentRemoverTestB.php';
        $cleanContents = ClassContentRemover::remove_class_content($filePath);

        $expected = '<?php
 namespace TestNamespace\\Testing; use TestNamespace\\{Test1, Test2, Test3}; if (class_exists(\'Class\')) { class MyTest extends Test1 implements Test2 {} class MyTest2 {} }';

        $this->assertEquals($expected, $cleanContents);
    }

    public function testRemoveClassContentNoClass()
    {
        $filePath = dirname(__FILE__) . '/fixtures/classcontentremover/ContentRemoverTestC.php';

        $cleanContents = ClassContentRemover::remove_class_content($filePath);

        $this->assertEmpty($cleanContents);
    }

    public function testRemoveClassContentSillyMethod()
    {
        $filePath = dirname(__FILE__) . '/fixtures/classcontentremover/ContentRemoverTestD.php';

        $cleanContents = ClassContentRemover::remove_class_content($filePath);

        $expected = '<?php
 class SomeClass {} class AnotherClass {}';

        $this->assertEquals($expected, $cleanContents);
    }
}
