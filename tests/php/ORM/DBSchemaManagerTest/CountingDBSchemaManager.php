<?php

namespace SilverStripe\ORM\Tests\DBSchemaManagerTest;

/**
 * Schema manager stub that reports a fixed set of tables, each with one field,
 * and counts how often each schema list is read from the "database".
 */
class CountingDBSchemaManager extends TestDBSchemaManager
{
    /**
     * Number of reads per method, keyed by method name and then by table name
     * ('' for tableList()).
     *
     * @var array<string, array<string, int>>
     */
    private array $reads = [];

    /**
     * @param string[] $tables Names of the tables that exist
     */
    public function __construct(private readonly array $tables)
    {
    }

    public function getReadCount(string $method, string $table = ''): int
    {
        return $this->reads[$method][$table] ?? 0;
    }

    public function hasTable($tableName)
    {
        $this->countRead('hasTable', $tableName);
        return in_array($tableName, $this->tables, true);
    }

    public function fieldList($table)
    {
        $this->countRead('fieldList', $table);
        return ['Title' => 'varchar(255)'];
    }

    public function indexList($table)
    {
        $this->countRead('indexList', $table);
        return [];
    }

    public function tableList()
    {
        $this->countRead('tableList', '');
        return array_combine(array_map('strtolower', $this->tables), $this->tables);
    }

    private function countRead(string $method, string $table): void
    {
        $this->reads[$method][$table] = $this->getReadCount($method, $table) + 1;
    }
}
