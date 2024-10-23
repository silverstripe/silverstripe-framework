<?php

namespace SilverStripe\ORM\Tests;

use ReflectionClass;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\ORM\DB;
use SilverStripe\Control\Director;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\Tests\DBReplicaTest\TestController;
use SilverStripe\ORM\Tests\DBReplicaTest\TestObject;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;
use SilverStripe\ORM\DataQuery;
use PHPUnit\Framework\Attributes\DataProvider;

class DBReplicaTest extends FunctionalTest
{
    protected static $extra_dataobjects = [
        TestObject::class,
    ];

    protected static $fixture_file = 'DBReplicaTest.yml';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupConfigsAndConnections(true);
        // Set DB:$mustUsePrimary to true to allow using replicas
        // This is disabled by default in SapphireTest::setUpBeforeClass()
        // Also reset mustUsePrimary after using mutable sql to create yml fixtures
        // and also because by default an ADMIN user is logged in when using fixtures in SapphireTest::setUp()
        // and also prevent tests from affecting subsequent tests
        (new ReflectionClass(DB::class))->setStaticPropertyValue('mustUsePrimary', false);
    }

    protected function tearDown(): void
    {
        $this->setupConfigsAndConnections(false);
        // Reset DB:$mustUsePrimary to true which is the default set by SapphireTest::setUpBeforeClass()
        (new ReflectionClass(DB::class))->setStaticPropertyValue('mustUsePrimary', true);
        parent::tearDown();
    }
    
    public function testUsesReplica(): void
    {
        // Assert uses replica by default
        TestObject::get()->count();
        $this->assertSame('replica_01', $this->getLastConnectionName());
        // Assert uses primary when using withPrimary()
        DB::withPrimary(fn() => TestObject::get()->count());
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
        // Assert that withPrimary() was only temporary
        TestObject::get()->count();
        $this->assertSame('replica_01', $this->getLastConnectionName());
        // Assert DB::setMustUsePrimary() forces primary from now on
        DB::setMustUsePrimary();
        TestObject::get()->count();
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
    }

    public function testMutableSql(): void
    {
        // Assert that using mutable sql in an ORM method with a dataclass uses primary
        TestObject::create(['Title' => 'testing'])->write();
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
        // Assert that now all subsequent queries use primary
        TestObject::get()->count();
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
    }

    public function testMutableSqlDbQuery(): void
    {
        // Assert that using mutable sql in DB::query() uses primary
        DB::query('INSERT INTO "DBReplicaTest_TestObject" ("Title") VALUES (\'testing\')');
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
        // Assert that now all subsequent queries use primary
        TestObject::get()->count();
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
    }

    public function testMutableSqlDbPreparedQuery(): void
    {
        // Assert that using mutable sql in DB::prepared_query() uses primary
        DB::prepared_query('INSERT INTO "DBReplicaTest_TestObject" ("Title") VALUES (?)', ['testing']);
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
        // Assert that now all subsequent queries use primary
        TestObject::get()->count();
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
    }

    #[DataProvider('provideSetCurrentUser')]
    public function testSetCurrentUser(string $firstName, string $expected): void
    {
        $member = Member::get()->find('FirstName', $firstName);
        Security::setCurrentUser($member);
        TestObject::get()->count();
        $this->assertSame($expected, $this->getLastConnectionName());
    }

    public function testDataObjectMustUsePrimaryDb(): void
    {
        // Assert that DataList::getIterator() respect DataObject.must_use_primary_db
        foreach (TestObject::get() as $object) {
            $object->Title = 'test2';
        }
        $this->assertSame('replica_01', $this->getLastConnectionName());
        foreach (Group::get() as $group) {
            $group->Title = 'test2';
        }
        $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName());
        // Assert that DataQuery methods without params respect DataObject.must_use_primary_db
        $methods = [
            'count',
            'exists',
            'firstRow',
            'lastRow'
        ];
        foreach ($methods as $method) {
            (new DataQuery(TestObject::class))->$method();
            $this->assertSame('replica_01', $this->getLastConnectionName(), "method is $method");
            (new DataQuery(Group::class))->$method();
            $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName(), "method is $method");
        }
        // Assert that DataQuery methods with a param respect DataObject.must_use_primary_db
        $methods = [
            'max',
            'min',
            'avg',
            'sum',
            'column',
        ];
        foreach ($methods as $method) {
            (new DataQuery(TestObject::class))->$method('ID');
            $this->assertSame('replica_01', $this->getLastConnectionName(), "method is $method");
            (new DataQuery(Group::class))->$method('ID');
            $this->assertSame(DB::CONN_PRIMARY, $this->getLastConnectionName(), "method is $method");
        }
    }

    public static function provideSetCurrentUser(): array
    {
        return [
            'non_cms_user' => [
                'firstName' => 'random',
                'expected' => 'replica_01'
            ],
            'cms_user' => [
                'firstName' => 'cmsuser',
                'expected' => DB::CONN_PRIMARY
            ],
        ];
    }

    public static function provideRoutes(): array
    {
        return [
            'normal_route' => [
                'path' => 'test',
                'expected' => 'replica_01'
            ],
            'security_route' => [
                'path' => 'Security/login',
                'expected' => DB::CONN_PRIMARY
            ],
            'dev_route' => [
                'path' => 'dev/tasks',
                'expected' => DB::CONN_PRIMARY
            ],
            'dev_in_path_but_not_dev_route' => [
                'path' => 'test/dev',
                'expected' => 'replica_01'
            ],
            // Note that we are not testing a missing route because in recipe-core specifically
            // there apprears to be no database calls made at all when the route is missing
            // so the last connection name is recorded as 'primary', which was as part of setting
            // up the unit test class, rather the simulated GET request in the unit test
        ];
    }

    #[DataProvider('provideRoutes')]
    public function testRoutes(string $path, string $expected): void
    {
        // Create a custom rule to test our controller that should default to using a replica
        $rules = Config::inst()->get(Director::class, 'rules');
        $rules['test/dev'] = TestController::class;
        $rules['test'] = TestController::class;
        // Ensure that routes staring with '$' are at the bottom of the assoc array index and don't override
        // our new 'test' route
        uksort($rules, fn($a, $b) => str_starts_with($a, '$') ? 1 : (str_starts_with($b, '$') ? -1 : 0));
        Config::modify()->set(Director::class, 'rules', $rules);
        $this->get($path);
        $this->assertSame($expected, $this->getLastConnectionName());
    }

    public static function provideHasReplicaConfig(): array
    {
        return [
            'no_replica' => [
                'includeReplica' => false,
                'expected' => false
            ],
            'with_replica' => [
                'includeReplica' => true,
                'expected' => true
            ],
        ];
    }

    #[DataProvider('provideHasReplicaConfig')]
    public function testHasReplicaConfig(bool $includeReplica, bool $expected): void
    {
        $this->assertTrue(DB::hasReplicaConfig());
        $primaryConfig = DB::getConfig(DB::CONN_PRIMARY);
        $config = [DB::CONN_PRIMARY => $primaryConfig];
        if ($includeReplica) {
            $config['replica_01'] = $primaryConfig;
        }
        (new ReflectionClass(DB::class))->setStaticPropertyValue('configs', $config);
        $this->assertSame($expected, DB::hasReplicaConfig());
    }

    public function testHasConfig(): void
    {
        $this->assertFalse(DB::hasConfig('lorem'));
        DB::setConfig(['type' => 'lorem'], 'lorem');
        $this->assertTrue(DB::hasConfig('lorem'));
    }

    public function testGetReplicaConfigKey(): void
    {
        $this->assertSame('replica_03', DB::getReplicaConfigKey(3));
        $this->assertSame('replica_58', DB::getReplicaConfigKey(58));
    }

    /**
     * Using reflection, set DB::configs and DB::connections with a fake a replica connection
     * that points to the same connection as the primary connection.
     */
    private function setupConfigsAndConnections($includeReplica = true): void
    {
        $reflector = new ReflectionClass(DB::class);
        $primaryConfig = DB::getConfig(DB::CONN_PRIMARY);
        $configs = [DB::CONN_PRIMARY => $primaryConfig];
        if ($includeReplica) {
            $configs['replica_01'] = $primaryConfig;
        }
        $reflector->setStaticPropertyValue('configs', $configs);
        // Create connections
        $primaryConnection = DB::get_conn(DB::CONN_PRIMARY);
        $connections = [DB::CONN_PRIMARY => $primaryConnection];
        if ($includeReplica) {
            $connections['replica_01'] = $primaryConnection;
        }
        $reflector->setStaticPropertyValue('connections', $connections);
    }

    /**
     * Get the last connection name used by the DB class. This shows if a replica was used.
     */
    private function getLastConnectionName(): string
    {
        return (new ReflectionClass(DB::class))->getStaticPropertyValue('lastConnectionName');
    }
}
