<?php
/**
 * @package sapphire
 * @subpackage tests
 * @author Ingo Schommer
 */
class RandomGeneratorTest extends SapphireTest {

	function testGenerateEntropy() {
		$r = new RandomGenerator();
		$this->assertNotNull($r->generateEntropy());
		$this->assertNotEquals($r->generateEntropy(), $r->generateEntropy());
	}
	
	function testGenerateHash() {
		$r = new RandomGenerator();
		$this->assertNotNull($r->randomToken());
		$this->assertNotEquals($r->randomToken(), $r->randomToken());
	}
	
	function testGenerateHashWithAlgorithm() {
		$r = new RandomGenerator();
		$this->assertNotNull($r->randomToken('md5'));
		$this->assertNotEquals($r->randomToken(), $r->randomToken('md5'));
	}
	
}
