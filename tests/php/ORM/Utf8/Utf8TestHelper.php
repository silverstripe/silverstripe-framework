<?php

namespace SilverStripe\Tests\ORM\Utf8;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DB;

class Utf8TestHelper implements TestOnly
{
    /**
     * @var string|null
     */
    private $dbVersion = null;

    public function getUpdatedUtfCharsetForCurrentDB(string $charset): string
    {
        if ($charset !== 'utf8') {
            return $charset;
        }
        return $this->isMySqlGte80() || $this->isMariaDBGte106() ? 'utf8mb3' : 'utf8';
    }

    public function getUpdatedUtfCollationForCurrentDB(string $collation): string
    {
        if ($collation === 'utf8_general_ci') {
            return $this->isMariaDBGte106() || $this->isMySqlGte8030()
                ? 'utf8mb3_general_ci' : 'utf8_general_ci';
        }
        if ($collation === 'utf8_unicode_520_ci') {
            return $this->isMariaDBGte106() || $this->isMySqlGte8030()
                ? 'utf8mb3_unicode_520_ci' : 'utf8_unicode_520_ci';
        }
        return $collation;
    }

    /**
     * MySQL has used utf8 as an alias for utf8mb3
     * Beginning with MySQL 8.0.28, utf8mb3 is used
     * https://dev.mysql.com/doc/refman/8.0/en/charset-unicode-utf8mb3.html
     */
    private function isMySqlGte80(): bool
    {
        // Example MySQL version: 8.0.29
        if (preg_match('#^([0-9]+)\.[0-9]+\.[0-9]+$#', $this->getDBVersion(), $m)) {
            return (int) $m[1] >= 8;
        }
        return false;
    }

    /**
     * Starting with 8.0.30, utf8mb3 is reported for the collation as well
     * https://dev.mysql.com/doc/relnotes/mysql/8.0/en/news-8-0-30.html
     */
    private function isMySqlGte8030(): bool
    {
        // Example MySQL version: 8.0.29
        if (preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)$#', $this->getDBVersion(), $m)) {
            if ((int) $m[1] >= 8) {
                if ((int) $m[2] === 0) {
                    if ((int) $m[3] >= 30) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Until MariaDB 10.5, utf8mb3 was an alias for utf8.
     * From MariaDB 10.6, utf8 is by default an alias for utf8mb3
     * https://mariadb.com/kb/en/unicode/
     */
    private function isMariaDBGte106(): bool
    {
        // Example mariadb version: 5.5.5-10.6.8-mariadb-1:10.6.8+maria~focal
        if (preg_match('#([0-9]+)\.([0-9]+)\.[0-9]+-mariadb#', $this->getDBVersion(), $m)) {
            return (int) $m[1] >= 11 || ((int) $m[1] >= 10 && (int) $m[2] >= 6);
        }
        return false;
    }

    private function getDBVersion(): string
    {
        if (is_null($this->dbVersion)) {
            $this->dbVersion = strtolower(DB::get_conn()->getVersion());
        }
        return $this->dbVersion;
    }
}
