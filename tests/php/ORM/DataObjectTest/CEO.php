<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

class CEO extends Staff
{
    private static $table_name = 'DataObjectTest_CEO';

    private static $belongs_to = array(
        'Company' => Company::class . '.CEO',
        'PreviousCompany' => Company::class . '.PreviousCEO',
        'CompanyOwned' => Company::class . '.Owner'
    );
}
