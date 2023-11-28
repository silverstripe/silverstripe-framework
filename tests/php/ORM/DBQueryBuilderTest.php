<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\Connect\DBQueryBuilder;
use SilverStripe\ORM\Queries\SQLSelect;

class DBQueryBuilderTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testMultilineJoin()
    {
        $join = <<<JOIN
        INNER JOIN
        (SELECT DISTINCT "SiteTreeLink"."ClassName", "SiteTreeLink"."LastEdited", "SiteTreeLink"."Created", "SiteTreeLink"."LinkedID",
        "SiteTreeLink"."ParentID", "SiteTreeLink"."ParentClass", "SiteTreeLink"."ID" FROM "SiteTreeLink")
        AS "SiteTreeLink" ON "SiteTreeLink"."LinkedID" = "SiteTree"."ID"
        JOIN;
        $select = new SQLSelect('*', ['SomeTable', $join]);
        $builder = new DBQueryBuilder();

        $params = [];
        $this->assertSame('FROM SomeTable ' . $join, trim($builder->buildFromFragment($select, $params)));
    }
}
