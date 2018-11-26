<?php

namespace SilverStripe\Core\Tests\Startup;

use SilverStripe\Core\Startup\ConfirmationTokenChain;
use SilverStripe\Core\Startup\ParameterConfirmationToken;
use SilverStripe\Core\Startup\URLConfirmationToken;
use SilverStripe\Dev\SapphireTest;

class ConfirmationTokenChainTest extends SapphireTest
{
    protected function getTokenRequiringReload($requiresReload = true, $extraMethods = [])
    {
        $methods = array_merge(['reloadRequired'], $extraMethods);
        $mock = $this->createPartialMock(ParameterConfirmationToken::class, $methods);
        $mock->expects($this->any())
            ->method('reloadRequired')
            ->will($this->returnValue($requiresReload));
        return $mock;
    }

    protected function getTokenRequiringReloadIfError($requiresReload = true, $extraMethods = [])
    {
        $methods = array_merge(['reloadRequired', 'reloadRequiredIfError'], $extraMethods);
        $mock = $this->createPartialMock(ParameterConfirmationToken::class, $methods);
        $mock->expects($this->any())
            ->method('reloadRequired')
            ->will($this->returnValue(false));
        $mock->expects($this->any())
            ->method('reloadRequiredIfError')
            ->will($this->returnValue($requiresReload));
        return $mock;
    }

    public function testFilteredTokens()
    {
        $chain = new ConfirmationTokenChain();
        $chain->pushToken($tokenRequiringReload = $this->getTokenRequiringReload());
        $chain->pushToken($tokenNotRequiringReload = $this->getTokenRequiringReload(false));
        $chain->pushToken($tokenRequiringReloadIfError = $this->getTokenRequiringReloadIfError());
        $chain->pushToken($tokenNotRequiringReloadIfError = $this->getTokenRequiringReloadIfError(false));

        $reflectionMethod = new \ReflectionMethod(ConfirmationTokenChain::class, 'filteredTokens');
        $reflectionMethod->setAccessible(true);
        $tokens = iterator_to_array($reflectionMethod->invoke($chain));

        $this->assertContains($tokenRequiringReload, $tokens, 'Token requiring a reload was not returned');
        $this->assertNotContains($tokenNotRequiringReload, $tokens, 'Token not requiring a reload was returned');
        $this->assertContains($tokenRequiringReloadIfError, $tokens, 'Token requiring a reload on error was not returned');
        $this->assertNotContains($tokenNotRequiringReloadIfError, $tokens, 'Token not requiring a reload on error was returned');
    }

    public function testSuppressionRequired()
    {
        $chain = new ConfirmationTokenChain();
        $chain->pushToken($this->getTokenRequiringReload(false));
        $this->assertFalse($chain->suppressionRequired(), 'Suppression incorrectly marked as required');

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($this->getTokenRequiringReloadIfError(false));
        $this->assertFalse($chain->suppressionRequired(), 'Suppression incorrectly marked as required');

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($this->getTokenRequiringReload());
        $this->assertTrue($chain->suppressionRequired(), 'Suppression not marked as required');

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($this->getTokenRequiringReloadIfError());
        $this->assertFalse($chain->suppressionRequired(), 'Suppression incorrectly marked as required');
    }

    public function testSuppressTokens()
    {
        $mockToken = $this->getTokenRequiringReload(true, ['suppress']);
        $mockToken->expects($this->once())
            ->method('suppress');
        $secondMockToken = $this->getTokenRequiringReloadIfError(true, ['suppress']);
        $secondMockToken->expects($this->once())
            ->method('suppress');

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($mockToken);
        $chain->pushToken($secondMockToken);
        $chain->suppressTokens();
    }

    public function testReloadRequired()
    {
        $mockToken = $this->getTokenRequiringReload(true);
        $secondMockToken = $this->getTokenRequiringReload(false);

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($mockToken);
        $chain->pushToken($secondMockToken);
        $this->assertTrue($chain->reloadRequired());
    }

    public function testReloadRequiredIfError()
    {
        $mockToken = $this->getTokenRequiringReloadIfError(true);
        $secondMockToken = $this->getTokenRequiringReloadIfError(false);

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($mockToken);
        $chain->pushToken($secondMockToken);
        $this->assertTrue($chain->reloadRequiredIfError());
    }

    public function testParams()
    {
        $mockToken = $this->getTokenRequiringReload(true, ['params']);
        $mockToken->expects($this->once())
            ->method('params')
            ->with($this->isTrue())
            ->will($this->returnValue(['mockTokenParam' => '1']));
        $secondMockToken = $this->getTokenRequiringReload(true, ['params']);
        $secondMockToken->expects($this->once())
            ->method('params')
            ->with($this->isTrue())
            ->will($this->returnValue(['secondMockTokenParam' => '2']));

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($mockToken);
        $chain->pushToken($secondMockToken);
        $this->assertEquals(['mockTokenParam' => '1', 'secondMockTokenParam' => '2'], $chain->params(true));

        $mockToken = $this->getTokenRequiringReload(true, ['params']);
        $mockToken->expects($this->once())
            ->method('params')
            ->with($this->isFalse())
            ->will($this->returnValue(['mockTokenParam' => '1']));

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($mockToken);
        $this->assertEquals(['mockTokenParam' => '1'], $chain->params(false));
    }

    public function testGetRedirectUrlBase()
    {
        $mockUrlToken = $this->createPartialMock(URLConfirmationToken::class, ['reloadRequired', 'getRedirectUrlBase']);
        $mockUrlToken->expects($this->any())
            ->method('reloadRequired')
            ->will($this->returnValue(true));
        $mockUrlToken->expects($this->any())
            ->method('getRedirectUrlBase')
            ->will($this->returnValue('url-base'));

        $mockParameterToken = $this->createPartialMock(ParameterConfirmationToken::class, ['reloadRequired', 'getRedirectUrlBase']);
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

    public function testGetRedirectUrlParams()
    {
        $mockToken = $this->getTokenRequiringReload(true, ['params']);
        $mockToken->expects($this->once())
            ->method('params')
            ->will($this->returnValue(['mockTokenParam' => '1']));

        $secondMockToken = $this->getTokenRequiringReload(true, ['params']);
        $secondMockToken->expects($this->once())
            ->method('params')
            ->will($this->returnValue(['secondMockTokenParam' => '2']));

        $chain = new ConfirmationTokenChain();
        $chain->pushToken($mockToken);
        $chain->pushToken($secondMockToken);
        $params = $chain->getRedirectUrlParams();
        $this->assertEquals('1', $params['mockTokenParam']);
        $this->assertEquals('2', $params['secondMockTokenParam']);
    }
}
