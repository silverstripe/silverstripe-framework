<?php

namespace SilverStripe\Core\Tests\Startup;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Startup\ParameterConfirmationToken;
use SilverStripe\Core\Tests\Startup\ParameterConfirmationTokenTest\ParameterConfirmationTokenTest_Token;
use SilverStripe\Core\Tests\Startup\ParameterConfirmationTokenTest\ParameterConfirmationTokenTest_ValidToken;
use SilverStripe\Dev\SapphireTest;

class ParameterConfirmationTokenTest extends SapphireTest
{
    /**
     * @var HTTPRequest
     */
    protected $request = null;

    protected function setUp()
    {
        parent::setUp();
        $get = [];
        $get['parameterconfirmationtokentest_notoken'] = 'value';
        $get['parameterconfirmationtokentest_empty'] = '';
        $get['parameterconfirmationtokentest_withtoken'] = '1';
        $get['parameterconfirmationtokentest_withtokentoken'] = 'dummy';
        $get['parameterconfirmationtokentest_nulltoken'] = '1';
        $get['parameterconfirmationtokentest_nulltokentoken'] = null;
        $get['parameterconfirmationtokentest_emptytoken'] = '1';
        $get['parameterconfirmationtokentest_emptytokentoken'] = '';
        $get['BackURL'] = 'page?parameterconfirmationtokentest_backtoken=1';
        $this->request = new HTTPRequest('GET', 'anotherpage', $get);
        $this->request->setSession(new Session([]));
    }

    public function testParameterDetectsParameters()
    {
        $withoutToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_notoken', $this->request);
        $emptyParameter = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_empty', $this->request);
        $withToken = new ParameterConfirmationTokenTest_ValidToken('parameterconfirmationtokentest_withtoken', $this->request);
        $withoutParameter = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_noparam', $this->request);
        $nullToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_nulltoken', $this->request);
        $emptyToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_emptytoken', $this->request);
        $backToken = new ParameterConfirmationTokenTest_Token('parameterconfirmationtokentest_backtoken', $this->request);

        // Check parameter
        $this->assertTrue($withoutToken->parameterProvided());
        $this->assertTrue($emptyParameter->parameterProvided());  // even if empty, it's still provided
        $this->assertTrue($withToken->parameterProvided());
        $this->assertFalse($withoutParameter->parameterProvided());
        $this->assertTrue($nullToken->parameterProvided());
        $this->assertTrue($emptyToken->parameterProvided());
        $this->assertFalse($backToken->parameterProvided());

        // Check backurl
        $this->assertFalse($withoutToken->existsInReferer());
        $this->assertFalse($emptyParameter->existsInReferer());  // even if empty, it's still provided
        $this->assertFalse($withToken->existsInReferer());
        $this->assertFalse($withoutParameter->existsInReferer());
        $this->assertFalse($nullToken->existsInReferer());
        $this->assertFalse($emptyToken->existsInReferer());
        $this->assertTrue($backToken->existsInReferer());

        // Check token
        $this->assertFalse($withoutToken->tokenProvided());
        $this->assertFalse($emptyParameter->tokenProvided());
        $this->assertTrue($withToken->tokenProvided()); // Actually forced to true for this test
        $this->assertFalse($withoutParameter->tokenProvided());
        $this->assertFalse($nullToken->tokenProvided());
        $this->assertFalse($emptyToken->tokenProvided());
        $this->assertFalse($backToken->tokenProvided());

        // Check if reload is required
        $this->assertTrue($withoutToken->reloadRequired());
        $this->assertTrue($emptyParameter->reloadRequired());
        $this->assertFalse($withToken->reloadRequired());
        $this->assertFalse($withoutParameter->reloadRequired());
        $this->assertTrue($nullToken->reloadRequired());
        $this->assertTrue($emptyToken->reloadRequired());
        $this->assertFalse($backToken->reloadRequired());

        // Check if a reload is required in case of error
        $this->assertTrue($withoutToken->reloadRequiredIfError());
        $this->assertTrue($emptyParameter->reloadRequiredIfError());
        $this->assertFalse($withToken->reloadRequiredIfError());
        $this->assertFalse($withoutParameter->reloadRequiredIfError());
        $this->assertTrue($nullToken->reloadRequiredIfError());
        $this->assertTrue($emptyToken->reloadRequiredIfError());
        $this->assertTrue($backToken->reloadRequiredIfError());

        // Check redirect url
        $home = (BASE_URL ?: '/') . '?';
        $current = Controller::join_links(BASE_URL, '/', 'anotherpage') . '?';
        $this->assertStringStartsWith($current, $withoutToken->redirectURL());
        $this->assertStringStartsWith($current, $emptyParameter->redirectURL());
        $this->assertStringStartsWith($current, $nullToken->redirectURL());
        $this->assertStringStartsWith($current, $emptyToken->redirectURL());
        $this->assertStringStartsWith($home, $backToken->redirectURL());

        // Check suppression
        $this->assertEquals('value', $this->request->getVar('parameterconfirmationtokentest_notoken'));
        $withoutToken->suppress();
        $this->assertNull($this->request->getVar('parameterconfirmationtokentest_notoken'));
    }

    public function testPrepareTokens()
    {
        // Test priority ordering
        $token = ParameterConfirmationToken::prepare_tokens(
            [
                'parameterconfirmationtokentest_notoken',
                'parameterconfirmationtokentest_empty',
                'parameterconfirmationtokentest_noparam'
            ],
            $this->request
        );
        // Test no invalid tokens
        $this->assertEquals('parameterconfirmationtokentest_empty', $token->getName());
        $token = ParameterConfirmationToken::prepare_tokens(
            [ 'parameterconfirmationtokentest_noparam' ],
            $this->request
        );
        $this->assertEmpty($token);

        // Test backurl token
        $token = ParameterConfirmationToken::prepare_tokens(
            [ 'parameterconfirmationtokentest_backtoken' ],
            $this->request
        );
        $this->assertEquals('parameterconfirmationtokentest_backtoken', $token->getName());
    }

    public function dataProviderURLs()
    {
        return [
            [''],
            ['/'],
            ['bar'],
            ['bar/'],
            ['/bar'],
            ['/bar/'],
        ];
    }

    /**
     * currentAbsoluteURL needs to handle base or url being missing, or any combination of slashes.
     *
     * There should always be exactly one slash between each part in the result, and any trailing slash
     * should be preserved.
     *
     * @dataProvider dataProviderURLs
     */
    public function testCurrentAbsoluteURLHandlesSlashes($url)
    {
        $this->request->setUrl($url);

        $token = new ParameterConfirmationTokenTest_Token(
            'parameterconfirmationtokentest_parameter',
            $this->request
        );
        $expected = rtrim(Controller::join_links(BASE_URL, '/', $url), '/') ?: '/';
        $this->assertEquals($expected, $token->currentURL(), "Invalid redirect for request url $url");
    }
}
