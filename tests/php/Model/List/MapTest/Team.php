<?php

namespace SilverStripe\Model\Tests\List\MapTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;

/**
 * @property string Title
 * @property string DatabaseField
 * @property array SalaryCap
 * @property string FoundationYear
 * @property bool CustomHydratedField
 * @method HasManyList SubTeams()
 * @method HasManyList Comments()
 */
class Team extends DataObject implements TestOnly
{
    private static $table_name = 'MapTest_Team';

    private static $db = [
        'Title' => 'Varchar',
        'DatabaseField' => 'HTMLVarchar',
        'NumericField' => 'Int',
    ];

    private static $has_many = [
        'SubTeams' => SubTeam::class,
        'Comments' => TeamComment::class,
    ];

    private static $default_sort = '"Title"';

    public function MyTitle()
    {
        return 'Team ' . $this->Title;
    }
}
