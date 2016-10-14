<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

class CEO extends Staff
{
	private static $table_name = 'DataObjectTest_CEO';

	private static $belongs_to = array(
		'Company' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Company.CEO',
		'PreviousCompany' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Company.PreviousCEO',
		'CompanyOwned' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Company.Owner'
	);
}
