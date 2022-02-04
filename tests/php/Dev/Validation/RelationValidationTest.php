<?php

namespace SilverStripe\Dev\Tests\Validation;

use Page;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Validation\RelationValidationService;

class RelationValidationTest extends SapphireTest
{
    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        Team::class,
        Member::class,
        Hat::class,
        Freelancer::class,
    ];

    /**
     * @param string|null $class
     * @param string|null $field
     * @param array $value
     * @param array $expected
     * @dataProvider validateCasesProvider
     */
    public function testValidation(?string $class, ?string $field, array $value, array $expected): void
    {
        if ($class && $field) {
            Config::modify()->set($class, $field, $value);
        }

        $data = RelationValidationService::singleton()->inspectClasses([
            Team::class,
            Member::class,
            Hat::class,
            Freelancer::class,
        ]);

        $this->assertSame($expected, $data);
    }

    /**
     * @param string $class
     * @param string|null $relation
     * @param array $config
     * @param bool $expected
     * @dataProvider ignoredClassesProvider
     */
    public function testIgnoredClass(string $class, ?string $relation, array $config, bool $expected): void
    {
        $service = RelationValidationService::singleton();

        foreach ($config as $name => $value) {
            $service->config()->set($name, $value);
        }

        $result = $service->isIgnored($class, $relation);

        $this->assertEquals($expected, $result);
    }

    public function validateCasesProvider(): array
    {
        return [
            'correct setup' => [
                null,
                null,
                [],
                [],
            ],
            'ambiguous has_one - no relation name' => [
                Hat::class,
                'belongs_to',
                [
                    'Hatter' => Member::class,
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Member / Hat : Back relation not found or ambiguous (needs class.relation format)',
                    'SilverStripe\Dev\Tests\Validation\Hat / Hatter : Relation is not in the expected format (needs class.relation format)',
                ],
            ],
            'ambiguous has_one - incorrect relation name' => [
                Hat::class,
                'belongs_to',
                [
                    'Hatter' => Member::class . '.ObviouslyWrong',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Member / Hat : Back relation not found or ambiguous (needs class.relation format)',
                    'SilverStripe\Dev\Tests\Validation\Hat / Hatter : Back relation not found',
                ],
            ],
            'ambiguous has_one - too many relations' => [
                Hat::class,
                'belongs_to',
                [
                    'Hatter' => Member::class . '.Hat',
                    'HatterCopy' => Member::class . '.Hat',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Member / Hat : Back relation is ambiguous',
                ],
            ],
            'invalid has one' => [
                Member::class,
                'has_one',
                [
                    'HomeTeam' => Team::class . '.UnnecessaryRelation',
                    'Hat' => Hat::class,
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Member / HomeTeam : Relation SilverStripe\Dev\Tests\Validation\Team.UnnecessaryRelation is not in the expected format (needs class only format).'
                ],
            ],
            'ambiguous has_many - no relation name' => [
                Team::class,
                'has_many',
                [
                    'Members' => Member::class,
                    'FreelancerMembers' => Freelancer::class . '.TemporaryTeam',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Team / Members : Relation is not in the expected format (needs class.relation format)',
                    'SilverStripe\Dev\Tests\Validation\Member / HomeTeam : Back relation not found or ambiguous (needs class.relation format)',
                ],
            ],
            'ambiguous has_many - incorrect relation name' => [
                Team::class,
                'has_many',
                [
                    'Members' => Member::class . '.ObviouslyWrong',
                    'FreelancerMembers' => Freelancer::class . '.TemporaryTeam',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Team / Members : Back relation not found or ambiguous (needs class.relation format)',
                    'SilverStripe\Dev\Tests\Validation\Member / HomeTeam : Back relation not found or ambiguous (needs class.relation format)',
                ],
            ],
            'ambiguous has_many - too many relations' => [
                Team::class,
                'has_many',
                [
                    'Members' => Member::class . '.HomeTeam',
                    'MembersCopy' => Member::class . '.HomeTeam',
                    'FreelancerMembers' => Freelancer::class . '.TemporaryTeam',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Member / HomeTeam : Back relation is ambiguous',
                ],
            ],
            'ambiguous many_many - no relation name' => [
                Hat::class,
                'belongs_many_many',
                [
                    'TeamHats' => Team::class,
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Team / ReserveHats : Back relation not found or ambiguous (needs class.relation format)',
                    'SilverStripe\Dev\Tests\Validation\Hat / TeamHats : Relation is not in the expected format (needs class.relation format)',
                ],
            ],
            'ambiguous many_many - incorrect relation name' => [
                Hat::class,
                'belongs_many_many',
                [
                    'TeamHats' => Team::class . '.ObviouslyWrong',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Team / ReserveHats : Back relation not found or ambiguous (needs class.relation format)',
                    'SilverStripe\Dev\Tests\Validation\Hat / TeamHats : Back relation not found',
                ],
            ],
            'ambiguous many_many - too many relations' => [
                Hat::class,
                'belongs_many_many',
                [
                    'TeamHats' => Team::class . '.ReserveHats',
                    'TeamHatsCopy' => Team::class . '.ReserveHats',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Team / ReserveHats : Back relation is ambiguous',
                ],
            ],
            'ambiguous many_many through - no relation name' => [
                Member::class,
                'belongs_many_many',
                [
                    'FreelancerTeams' => Team::class,
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Team / Freelancers : Back relation not found or ambiguous (needs class.relation format)',
                    'SilverStripe\Dev\Tests\Validation\Member / FreelancerTeams : Relation is not in the expected format (needs class.relation format)',
                ],
            ],
            'ambiguous many_many through - incorrect relation name' => [
                Member::class,
                'belongs_many_many',
                [
                    'FreelancerTeams' => Team::class . '.ObviouslyWrong',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Team / Freelancers : Back relation not found or ambiguous (needs class.relation format)',
                    'SilverStripe\Dev\Tests\Validation\Member / FreelancerTeams : Back relation not found',
                ],
            ],
            'ambiguous many_many through - too many relations' => [
                Member::class,
                'belongs_many_many',
                [
                    'FreelancerTeams' => Team::class . '.Freelancers',
                    'FreelancerTeamsCopy' => Team::class . '.Freelancers',
                ],
                [
                    'SilverStripe\Dev\Tests\Validation\Team / Freelancers : Back relation is ambiguous',
                ],
            ],
        ];
    }

    public function ignoredClassesProvider(): array
    {
        return [
            'class default' => [
                Team::class,
                null,
                [],
                true,
            ],
            'class relation default' => [
                Team::class,
                'Members',
                [],
                true,
            ],
            'page should by included by default (empty namespace)' => [
                Page::class,
                null,
                [],
                false,
            ],
            'class relation via allow rules' => [
                Team::class,
                'Members',
                [
                    'allow_rules' => ['app' => 'SilverStripe\Dev\Tests\Validation'],
                ],
                false,
            ],
            'class included via allow rules but overwritten by deny rules' => [
                Team::class,
                null,
                [
                    'allow_rules' => ['app' => 'SilverStripe\Dev\Tests\Validation'],
                    'deny_rules' => [Team::class],
                ],
                true,
            ],
            'class relation included via allow rules but overwritten by deny rules' => [
                Team::class,
                'Members',
                [
                    'allow_rules' => ['app' => 'SilverStripe\Dev\Tests\Validation'],
                    'deny_rules' => [Team::class],
                ],
                true,
            ],
            'class relation included via allow rules but overwritten by deny relations' => [
                Team::class,
                'Members',
                [
                    'allow_rules' => ['app' => 'SilverStripe\Dev\Tests\Validation'],
                    'deny_relations' => [Team::class . '.Members'],
                ],
                true,
            ],
            'class relation included via allow rules and not overwritten by deny relations of other class' => [
                Member::class,
                'HomeTeam',
                [
                    'allow_rules' => ['app' => 'SilverStripe\Dev\Tests\Validation'],
                    'deny_rules' => [Team::class],
                    'deny_relations' => [Team::class . '.Members'],
                ],
                false,
            ],
        ];
    }
}
