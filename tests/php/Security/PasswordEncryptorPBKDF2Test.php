<?php

namespace SilverStripe\Security\Tests;

use League\Flysystem\Exception;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\PasswordEncryptorPBKDF2;

class PasswordEncryptorPBKDF2Test extends SapphireTest
{
    public function testGetIterations()
    {
        $encryptor = new PasswordEncryptorPBKDF2('sha512', 12345);
        $this->assertSame(12345, $encryptor->getIterations());
    }

    public function testEncrypt()
    {
        $encryptor = new PasswordEncryptorPBKDF2('sha512', 10000);
        $salt = 'predictablesaltforunittesting';
        $result = $encryptor->encrypt('opensesame', $salt);
        $this->assertSame(
            '6bafcacb90',
            substr($result ?? '', 0, 10),
            'Hashed password with predictable salt did not match fixtured expectation'
        );
    }

    public function testThrowsExceptionWhenInvalidAlgorithmIsProvided()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hash algorithm "foobar" not found in hash_hmac_algos()');
        new PasswordEncryptorPBKDF2('foobar');
    }
}
