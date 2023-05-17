<?php

namespace SilverStripe\ORM\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\DataQuery_SubGroup;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use LogicException;
use InvalidArgumentException;

class DataQuery_SubGroupTest extends SapphireTest
{
    public function testConstructorException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$clause must be either WHERE or HAVING');
        new DataQuery_SubGroup(new DataQuery(Team::class), 'AND', 'INVALID');
    }

    public function testWhereException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot call where() when clause is set to HAVING');
        $query = new DataQuery_SubGroup(new DataQuery(Team::class), 'AND', 'HAVING');
        $query->where([]);
    }

    public function testWhereAnyException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot call whereAny() when clause is set to HAVING');
        $query = new DataQuery_SubGroup(new DataQuery(Team::class), 'AND', 'HAVING');
        $query->whereAny([]);
    }

    public function testHavingException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot call having() when clause is set to WHERE');
        $query = new DataQuery_SubGroup(new DataQuery(Team::class), 'AND', 'WHERE');
        $query->having([]);
    }
}
