<?php

class ConfirmationTokenChainTest extends SapphireTest {

	protected function getTokenRequiringReload($requiresReload = true, $extraMethods = array()) {
		$methods = array_merge(array('reloadRequired'), $extraMethods);
		$mock = $this->getMockBuilder('ParameterConfirmationToken')
			->disableOriginalConstructor()
			->setMethods($methods)
			->getMock();

		$mock->expects($this->any())
			->method('reloadRequired')
			->will($this->returnValue($requiresReload));

		return $mock;
	}

	public function testFilteredTokens() {
		$chain = new ConfirmationTokenChain();
		$chain->pushToken($tokenRequiringReload = $this->getTokenRequiringReload());
		$chain->pushToken($tokenNotRequiringReload = $this->getTokenRequiringReload(false));

		$reflectionMethod = new ReflectionMethod('ConfirmationTokenChain', 'filteredTokens');
		$reflectionMethod->setAccessible(true);
		$tokens = $reflectionMethod->invoke($chain);

		$this->assertContains($tokenRequiringReload, $tokens, 'Token requiring a reload was not returned');
		$this->assertNotContains($tokenNotRequiringReload, $tokens, 'Token not requiring a reload was returned');
	}

	public function testSuppressionRequired() {
		$chain = new ConfirmationTokenChain();
		$chain->pushToken($this->getTokenRequiringReload(false));
		$this->assertFalse($chain->suppressionRequired(), 'Suppression incorrectly marked as required');

		$chain = new ConfirmationTokenChain();
		$chain->pushToken($this->getTokenRequiringReload());
		$this->assertTrue($chain->suppressionRequired(), 'Suppression not marked as required');
	}

	public function testSuppressTokens() {
		$mockToken = $this->getTokenRequiringReload(true, array('suppress'));
		$mockToken->expects($this->once())
			->method('suppress');

		$chain = new ConfirmationTokenChain();
		$chain->pushToken($mockToken);
		$chain->suppressTokens();
	}

	public function testReloadRequired() {
		$mockToken = $this->getTokenRequiringReload(true);
		$secondMockToken = $this->getTokenRequiringReload(false);

		$chain = new ConfirmationTokenChain();
		$chain->pushToken($mockToken);
		$chain->pushToken($secondMockToken);
		$this->assertTrue($chain->reloadRequired());
	}

	public function testParams() {
		$mockToken = $this->getTokenRequiringReload(true, array('params'));
		$mockToken->expects($this->once())
			->method('params')
			->with($this->isTrue())
			->will($this->returnValue(array('mockTokenParam' => '1')));
		$secondMockToken = $this->getTokenRequiringReload(true, array('params'));
		$secondMockToken->expects($this->once())
			->method('params')
			->with($this->isTrue())
			->will($this->returnValue(array('secondMockTokenParam' => '2')));

		$chain = new ConfirmationTokenChain();
		$chain->pushToken($mockToken);
		$chain->pushToken($secondMockToken);
		$this->assertEquals(array('mockTokenParam' => '1', 'secondMockTokenParam' => '2'), $chain->params(true));

		$mockToken = $this->getTokenRequiringReload(true, array('params'));
		$mockToken->expects($this->once())
			->method('params')
			->with($this->isFalse())
			->will($this->returnValue(array('mockTokenParam' => '1')));

		$chain = new ConfirmationTokenChain();
		$chain->pushToken($mockToken);
		$this->assertEquals(array('mockTokenParam' => '1'), $chain->params(false));
	}

	public function testGetRedirectUrlBase() {
		$mockUrlToken = $this->getMockBuilder('URLConfirmationToken')
			->disableOriginalConstructor()
			->setMethods(array('reloadRequired', 'getRedirectUrlBase'))
			->getMock();
		$mockUrlToken->expects($this->any())
			->method('reloadRequired')
			->will($this->returnValue(true));
		$mockUrlToken->expects($this->any())
			->method('getRedirectUrlBase')
			->will($this->returnValue('url-base'));

		$mockParameterToken = $this->getMockBuilder('ParameterConfirmationToken')
			->disableOriginalConstructor()
			->setMethods(array('reloadRequired', 'getRedirectUrlBase'))
			->getMock();
		$mockParameterToken->expects($this->any())
			->method('reloadRequired')
			->will($this->returnValue(true));
		$mockParameterToken->expects($this->any())
			->method('getRedirectUrlBase')
			->will($this->returnValue('parameter-base'));

		$chain = new ConfirmationTokenChain();
		$chain->pushToken($mockParameterToken);
		$chain->pushToken($mockUrlToken);
		$this->assertEquals('url-base', $chain->getRedirectUrlBase(), 'URLConfirmationToken url base should take priority');

		// Push them in reverse order to check priority still correct
		$chain = new ConfirmationTokenChain();
		$chain->pushToken($mockUrlToken);
		$chain->pushToken($mockParameterToken);
		$this->assertEquals('url-base', $chain->getRedirectUrlBase(), 'URLConfirmationToken url base should take priority');
	}

	public function testGetRedirectUrlParams() {
		$mockToken = $this->getTokenRequiringReload(true, array('params'));
		$mockToken->expects($this->once())
			->method('params')
			->will($this->returnValue(array('mockTokenParam' => '1')));

		$secondMockToken = $this->getTokenRequiringReload(true, array('params'));
		$secondMockToken->expects($this->once())
			->method('params')
			->will($this->returnValue(array('secondMockTokenParam' => '2')));

		$chain = new ConfirmationTokenChain();
		$chain->pushToken($mockToken);
		$chain->pushToken($secondMockToken);
		$params = $chain->getRedirectUrlParams();
		$this->assertEquals('1', $params['mockTokenParam']);
		$this->assertEquals('2', $params['secondMockTokenParam']);
	}
}
