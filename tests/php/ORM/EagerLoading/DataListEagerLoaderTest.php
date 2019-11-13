<?php


namespace SilverStripe\ORM\Tests\EagerLoading;


use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataQueryExecutorInterface;
use SilverStripe\ORM\EagerLoading\DataListEagerLoader;
use SilverStripe\ORM\Tests\DataObjectTest\Player;
use SilverStripe\ORM\Tests\DataObjectTest\Team;
use SilverStripe\ORM\Tests\DataObjectTest\TeamComment;
use SilverStripe\ORM\Tests\EagerLoading\DataListEagerLoaderTest\DebuggableCachedDataQueryExecutor;
use SilverStripe\ORM\Tests\EagerLoading\DataListEagerLoaderTest\DebuggableNaiveDataQueryExecutor;

class DataListEagerLoaderTest extends SapphireTest
{
    // TODO: Why doesn't this load?
    //protected static $fixture_file = 'DataListEagerLoaderTest.yml';

    protected $usesDatabase = true;

    public static $extra_dataobjects = [
        Team::class,
        Player::class,
        TeamComment::class,
    ];

    public function testRelations()
    {
        $loader = new DataListEagerLoader();
        $loader->addRelations([
            'a1',
            'a2',
            'a3' => [
                'a3b1',
            ]
        ]);

        $loader->addRelations([
            'a4',
            'a3' => ['a3b2']
        ]);

        $loader->addRelations([
            'a5' => ['a5b1']
        ]);

        $this->assertEquals([
            'a1',
            'a2',
            'a3' => [
                'a3b1',
                'a3b2',
            ],
            'a4',
            'a5' => ['a5b1'],
        ], $loader->getRelations());
    }

    public function testExecute()
    {

    }

    public function testHasManyEagerLoading()
    {
        $this->buildState();
        $teamCount = Team::get()->count();

        $this->goNaive();

        foreach (Team::get() as $team) {
            foreach ($team->Comments() as $comment) {
            }
        }

        // N+1
        $this->assertEquals($teamCount + 1, $this->getExecutor()->getQueries());

        $this->goCached();

        $teams = Team::get()->sort('Title ASC')->with('Comments');
        $commentContent = '';
        foreach ($teams as $team) {
            foreach ($team->Comments() as $comment) {
                $commentContent .= $comment->Comment;
            }
        }
        $eagerLoads = 1;
        $this->assertEquals($eagerLoads + 1, $this->getExecutor()->getQueries());

        // Prove that we're getting cached results by changing a comment
        $newCommentContent = '';
        $teams = Team::get()->sort('Title ASC')->with('Comments');

        foreach ($teams as $team) {
            foreach ($team->Comments() as $comment) {
                $newCommentContent .= $comment->Comment;
            }
        }
        $this->assertEquals($commentContent, $newCommentContent);
        $comment = TeamComment::get()->first();
        $comment->Comment = 'CHANGED';
        $comment->write();

        $newCommentContent = '';
        $teams = Team::get()->sort('Title ASC')->with('Comments');

        foreach ($teams as $team) {
            foreach ($team->Comments() as $comment) {
                $newCommentContent .= $comment->Comment;
            }
        }

        $this->assertEquals($commentContent, $newCommentContent);

        $this->goNaive();

        $teams = Team::get()->sort('Title ASC');
        $newCommentContent = '';
        foreach ($teams as $team) {
            foreach ($team->Comments() as $comment) {
                $newCommentContent .= $comment->Comment;
            }
        }

        $this->assertNotEquals($commentContent, $newCommentContent);
    }

    public function testManyManyEagerLoading()
    {
        $this->buildState();
        $teamCount = Team::get()->count();

        $this->goNaive();

        foreach (Team::get() as $team) {
            $team->Players()->toArray();
        }

        // N+1
        $this->assertEquals($teamCount + 1, $this->getExecutor()->getQueries());

        $this->goCached();

        $teams = Team::get()->sort('Title ASC')->with('Players');
        $playerContent = '';
        foreach ($teams as $team) {
            foreach ($team->Players() as $player) {
                $playerContent .= $player->Surname;
            }
        }

        $eagerLoads = 1;
        $this->assertEquals($eagerLoads + 1, $this->getExecutor()->getQueries());

        // Prove that we're getting cached results by changing a player's name
        $newPlayerContent = '';
        $teams = Team::get()->sort('Title ASC')->with('Players');
        foreach ($teams as $team) {
            foreach ($team->Players() as $player) {
                $newPlayerContent .= $player->Surname;
            }
        }
        $this->assertEquals($playerContent, $newPlayerContent);
        $player = Player::get()->first();
        $player->Surname = 'CHANGED';
        $player->write();

        $newPlayerContent = '';
        $teams = Team::get()->sort('Title ASC')->with('Players');

        foreach ($teams as $team) {
            foreach ($team->Players() as $player) {
                $newPlayerContent .= $player->Surname;
            }
        }

        $this->assertEquals($playerContent, $newPlayerContent);

        $this->goNaive();

        $teams = Team::get()->sort('Title ASC');
        $newPlayerContent = '';
        foreach ($teams as $team) {
            foreach ($team->Players() as $player) {
                $newPlayerContent .= $player->Surname;
            }
        }

        $this->assertNotEquals($playerContent, $newPlayerContent);
    }


    public function testHasOneEagerLoading()
    {
        $this->buildState();
        $playerCount = Player::get()->count();

        $this->goNaive();

        foreach (Player::get() as $player) {
            $player->FavouriteTeam();
        }

        // N+1
        $this->assertEquals($playerCount + 1, $this->getExecutor()->getQueries());

        $this->goCached();

        $players = Player::get()->sort('Surname ASC')->with('FavouriteTeam');
        $playerContent = '';
        foreach ($players as $player) {
            $playerContent .= $player->FavouriteTeam()->Title;
        }
        $eagerLoads = 1;
        $this->assertEquals($eagerLoads + 1, $this->getExecutor()->getQueries());

        // Prove that we're getting cached results by changing a comment
        $newPlayerContent = '';
        foreach ($players as $player) {
            $newPlayerContent .= $player->FavouriteTeam()->Title;
        }
        $this->assertEquals($playerContent, $newPlayerContent);
        $player = Player::get()->filter('Surname', 'Edelman')->first();
        $team = Team::get()->byID($player->FavouriteTeamID);
        $team->Title = 'CHANGED';
        $team->write();

        $newPlayerContent = '';
        $players = Player::get()->sort('Surname ASC')->with('FavouriteTeam');
        foreach ($players as $player) {
            $newPlayerContent .= $player->FavouriteTeam()->Title;
        }

        $this->assertEquals($playerContent, $newPlayerContent);

        $this->goNaive();

        $players = Player::get()->sort('Surname ASC');
        $newPlayerContent = '';
        foreach ($players as $player) {
            $newPlayerContent .= $player->FavouriteTeam()->Title;
        }

        $this->assertNotEquals($playerContent, $newPlayerContent);
    }

    public function testNestedEagerLoading()
    {
        Config::modify()->set(TeamComment::class, 'default_sort', 'ID ASC');
        Config::modify()->set(Player::class, 'default_sort', 'ID ASC');
        $this->buildState();

        $this->goNaive();
        $expectedQueryCount = 1;
        $output = '';
        foreach(Team::get()->sort('Title ASC') as $team) {
            $output .= 'TEAM__' . $team->ID . '__' . $team->Title;
            foreach ($team->Comments() as $comment) {
                $output .= $comment->Comment;
            }
            $expectedQueryCount++;
            $captain = $team->Captain();
            if ($captain->exists()) {
                $expectedQueryCount++;
                $output .= 'CAPTAIN__' . $captain->ID;
            }
            $founder = $team->Founder();
            if ($founder->exists()) {
                $expectedQueryCount++;
                $output .= 'FOUNDER__' . $founder->ID;
            }
            $players = $team->Players()->toArray();
            $expectedQueryCount++;
            foreach ($players as $player) {
                $output .= 'PLAYER__' . $player->ID . '__' . $player->Surname . '__';
                $favourite = $player->FavouriteTeam();
                if ($favourite->exists()) {
                    $expectedQueryCount++;
                    $output .= $favourite->Title;
                }
            }
        }

        $this->assertEquals($expectedQueryCount, $this->getExecutor()->getQueries());

        $this->goCached();

        $teams = Team::get()->sort('Title ASC')->with([
            'Comments',
            'Captain',
            'Founder',
            'Players' => [
                'FavouriteTeam',
            ]
        ]);
        $newOutput = '';
        foreach($teams as $team) {
            $newOutput .= 'TEAM__' . $team->ID . '__' . $team->Title;
            foreach ($team->Comments() as $comment) {
                $newOutput .= $comment->Comment;
            }
            $captain = $team->Captain();
            if ($captain->exists()) {
                $newOutput .= 'CAPTAIN__' . $captain->ID;
            }
            $founder = $team->Founder();
            if ($founder->exists()) {
                $newOutput .= 'FOUNDER__' . $founder->ID;
            }
            $players = $team->Players()->toArray();
            foreach ($players as $player) {
                $newOutput .= 'PLAYER__' . $player->ID . '__' . $player->Surname .'__';
                $favourite = $player->FavouriteTeam();
                if ($favourite->exists()) {
                    $newOutput .= $favourite->Title;
                }
            }
        }

        $expectedQueryCount = 7;
        $this->assertEquals($output, $newOutput);
        $this->assertEquals($expectedQueryCount, $this->getExecutor()->getQueries());
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
        $team1->CaptainID = $player1->ID;
        $team1->write();

        $player2 = Player::create([
            'FirstName' => 'Rob',
            'Surname' => 'Gronkowski',
            'FavouriteTeamID' => $team6->ID,
        ]);
        $player2->write();
        $player2->Teams()->add($team1);
        $player2->Teams()->add($team3);
        $team2->CaptainID = $player2->ID;
        $team2->FounderID = $player1->ID;
        $team2->write();

        $player3 = Player::create([
            'FirstName' => 'Sony',
            'Surname' => 'Michel',
            'FavouriteTeamID' => $team7->ID,
        ]);
        $player3->write();
        $player3->Teams()->add($team1);
        $player3->Teams()->add($team2);
        $player3->Teams()->add($team6);
        $team3->CaptainID = $player3->ID;
        $team3->FounderID = $player2->ID;
        $team3->write();

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
        $team4->CaptainID = $player4->ID;
        $team4->FounderID = $player3->ID;
        $team4->write();

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
        $team5->CaptainID = $player5->ID;
        $team5->FounderID = $player4->ID;
        $team5->write();

        $player6 = Player::create([
            'FirstName' => 'Devon',
            'Surname' => 'McCourty',
            'FavouriteTeamID' => $team2->ID,
        ]);
        $player6->write();
        $player6->Teams()->add($team2);
        $player6->Teams()->add($team4);
        $player6->Teams()->add($team5);
        $team6->CaptainID = $player6->ID;
        $team6->FounderID = $player5->ID;
        $team6->write();

        $comment1 = TeamComment::create([
            'Name' => 'Rob Ryan',
            'Comment' => 'Worst team ever',
            'TeamID' => $team1->ID
        ]);
        $comment1->write();

        $comment2 = TeamComment::create([
            'Name' => 'Bill Bellichick',
            'Comment' => "We're on to Cincinnati",
            'TeamID' => $team1->ID
        ]);
        $comment2->write();

        $comment3 = TeamComment::create([
            'Name' => 'Andy Reid',
            'Comment' => "We'll get 'em next time",
            'TeamID' => $team2->ID
        ]);
        $comment3->write();

        $comment4 = TeamComment::create([
            'Name' => 'Tom Coughlin',
            'Comment' => "Bunch of Amateurs",
            'TeamID' => $team2->ID
        ]);
        $comment4->write();

        $comment5 = TeamComment::create([
            'Name' => 'Todd Bowles',
            'Comment' => "What time is the game?",
            'TeamID' => $team2->ID
        ]);
        $comment5->write();
    }

    protected function goNaive()
    {
        Injector::inst()->load([
            DataQueryExecutorInterface::class => [
                'class' => DebuggableNaiveDataQueryExecutor::class,
            ]
        ]);
        $this->getExecutor()->reset();
    }

    protected function goCached()
    {
        Injector::inst()->load([
            DataQueryExecutorInterface::class => [
                'class' => DebuggableCachedDataQueryExecutor::class,
            ]
        ]);
        $this->getExecutor()->reset();

    }

    protected function getExecutor()
    {
        return Injector::inst()->get(DataQueryExecutorInterface::class);
    }
}