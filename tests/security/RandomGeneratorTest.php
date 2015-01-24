<?php
/**
 * @package framework
 * @subpackage tests
 * @author Ingo Schommer
 */
class RandomGeneratorTest extends SapphireTest {

	public function testGenerateEntropy() {
		$r = new RandomGenerator();
		$this->assertNotNull($r->generateEntropy());
		$this->assertNotEquals($r->generateEntropy(), $r->generateEntropy());
	}

	public function testGenerateHash() {
		$r = new RandomGenerator();
		$this->assertNotNull($r->randomToken());
		$this->assertNotEquals($r->randomToken(), $r->randomToken());
	}

	public function testGenerateHashWithAlgorithm() {
		$r = new RandomGenerator();
		$this->assertNotNull($r->randomToken('md5'));
		$this->assertNotEquals($r->randomToken(), $r->randomToken('md5'));
	}

}
