<?php


namespace SilverStripe\ORM\Tests\EagerLoading;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\EagerLoading\HasOneEagerLoader;
use SilverStripe\ORM\QueryCache\CachedDataQueryExecutor;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\View\Tests\ViewableDataTest\Cached;

class HasOneEagerLoaderTest extends SapphireTest
{
    protected $usesDatabase = true;

    public static $extra_dataobjects = [
        Team::class,
        Player::class,
    ];


    public function testEagerLoadRelation()
    {
        $loader = new HasOneEagerLoader();
        $list = Player::get();
        $store = new CachedDataQueryExecutor();
//        $relatedRecords = $loader->eagerLoadRelation($list, 'FavouriteTeam', $store);
//        $ids = $relatedRecords->column('ID');
//        $this->assertEmpty($ids);
//
        $this->buildState();
        $list = Player::get();
        $relatedRecords = $loader->eagerLoadRelation($list, 'FavouriteTeam', $store);
        $titles = $relatedRecords->column('Title');
        sort($titles);
        $expectedTitles = [
            'The Tuis',
            'The Wetas',
            'The Kakas',
            'The Keas',
        ];
        sort($expectedTitles);

        $this->assertEquals($expectedTitles, $titles);
        $tuiID = Team::get()->filter('Title', 'The Tuis')->first()->ID;
        $result = $store->getCachedResult(
            Team::get()->filter('ID', $tuiID)->dataQuery(),
            CachedDataQueryExecutor::FIRST_ROW
        );
        $this->assertEquals($tuiID, $result->ID);
    }

    protected function buildState()
    {
        $team1 = Team::create(['Title' => 'The Tuis']);
        $team1->write();
        $team2 = Team::create(['Title' => 'The Wetas']);
        $team2->write();
        $team3 = Team::create(['Title' => 'The Kererus']);
        $team3->write();

        $team4 = Team::create(['Title' => 'The Hihis']);
        $team4->write();

        $team5 = Team::create(['Title' => 'The Tiekes']);
        $team5->write();

        $team6 = Team::create(['Title' => 'The Kakas']);
        $team6->write();

        $team7 = Team::create(['Title' => 'The Keas']);
        $team7->write();

        $player1 = Player::create([
            'FirstName' => 'Julian',
            'Surname' => 'Edelman',
            'FavouriteTeamID' => $team1->ID,
        ]);
        $player1->write();
        $player1->Teams()->add($team1);
        $player1->Teams()->add($team3);

        $player2 = Player::create([
            'FirstName' => 'Rob',
            'Surname' => 'Gronkowski',
            'FavouriteTeamID' => $team6->ID,
        ]);
        $player2->write();
        $player2->Teams()->add($team1);
        $player2->Teams()->add($team3);

        $player3 = Player::create([
            'FirstName' => 'Sony',
            'Surname' => 'Michel',
            'FavouriteTeamID' => $team7->ID,
        ]);
        $player3->write();
        $player3->Teams()->add($team1);
        $player3->Teams()->add($team2);
        $player3->Teams()->add($team6);

        $player4 = Player::create([
            'FirstName' => 'Chris',
            'Surname' => 'Hogan',
            'FavouriteTeamID' => $team2->ID,
        ]);
        $player4->write();
        $player4->Teams()->add($team1);
        $player4->Teams()->add($team3);
        $player4->Teams()->add($team5);
        $player4->Teams()->add($team7);

        $player5 = Player::create([
            'FirstName' => 'Stephon',
            'Surname' => 'Gilmore',
            'FavouriteTeamID' => $team2->ID,
        ]);
        $player5->write();
        $player5->Teams()->add($team2);
        $player5->Teams()->add($team3);
        $player5->Teams()->add($team5);
        $player5->Teams()->add($team4);

        $player6 = Player::create([
            'FirstName' => 'Devon',
            'Surname' => 'McCourty',
            'FavouriteTeamID' => $team2->ID,
        ]);
        $player6->write();
        $player6->Teams()->add($team2);
        $player6->Teams()->add($team4);
        $player6->Teams()->add($team5);

    }

}