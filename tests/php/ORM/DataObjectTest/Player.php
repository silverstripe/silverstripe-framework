<?php

namespace SilverStripe\ORM\Tests\DataObjectTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\Tests\DataObjectTest;
use SilverStripe\Security\Member;

class Player extends Member implements TestOnly
{
	private static $table_name = 'DataObjectTest_Player';

	private static $db = array(
		'IsRetired' => 'Boolean',
		'ShirtNumber' => 'Varchar',
	);

	private static $has_one = array(
		'FavouriteTeam' => DataObjectTest\Team::class,
	);

	private static $belongs_many_many = array(
		'Teams' => DataObjectTest\Team::class
	);

	private static $has_many = array(
		'Fans' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Fan.Favourite', // Polymorphic - Player fans
		'CaptainTeams' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Team.Captain',
		'FoundingTeams' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Team.Founder'
	);

	private static $belongs_to = array(
		'CompanyOwned' => 'SilverStripe\\ORM\\Tests\\DataObjectTest\\Company.Owner'
	);

	private static $searchable_fields = array(
		'IsRetired',
		'ShirtNumber'
	);
}
