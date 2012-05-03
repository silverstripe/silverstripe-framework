<?php
/**
 * @package framework
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
		$this->assertNotNull($r->generateHash());
		$this->assertNotEquals($r->generateHash(), $r->generateHash());
	}
	
	function testGenerateHashWithAlgorithm() {
		$r = new RandomGenerator();
		$this->assertNotNull($r->generateHash('md5'));
		$this->assertNotEquals($r->generateHash(), $r->generateHash('md5'));
	}
	
}
