<?php

namespace SilverStripe\Control\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Control\PolyCommandController;
use SilverStripe\Control\Session;
use SilverStripe\Control\Tests\PolyCommandControllerTest\TestPolyCommand;
use SilverStripe\Dev\SapphireTest;

class PolyCommandControllerTest extends SapphireTest
{
    protected $usesDatabase = false;

    public static function provideHandleRequest(): array
    {
        return [
            'no params' => [
                'exitCode' => 0,
                'params' => [],
                'allowed' => true,
                'expectedOutput' => "Has option 1: false<br>\noption 2 value: <br>\n",
            ],
            'with params' => [
                'exitCode' => 1,
                'params' => [
                    'option1' => true,
                    'option2' => 'abc',
                    'option3' => [
                        'val1',
                        'val2',
                    ],
                ],
                'allowed' => true,
                'expectedOutput' => "Has option 1: true<br>\noption 2 value: abc<br>\noption 3 value: val1<br>\noption 3 value: val2<br>\n",
            ],
            'explicit exit code' => [
                'exitCode' => 418,
                'params' => [],
                'allowed' => true,
                'expectedOutput' => "Has option 1: false<br>\noption 2 value: <br>\n",
            ],
            'not allowed to run' => [
                'exitCode' => 404,
                'params' => [],
                'allowed' => false,
                'expectedOutput' => "Has option 1: false<br>\noption 2 value: <br>\n",
            ],
        ];
    }

    #[DataProvider('provideHandleRequest')]
    public function testHandleRequest(int $exitCode, array $params, bool $allowed, string $expectedOutput): void
    {
        $polyCommand = new TestPolyCommand();
        TestPolyCommand::setCanRunInBrowser($allowed);
        if ($allowed) {
            // Don't set the exit code if not allowed to run - we want to test that it's correctly forced to 404
            $polyCommand->setExitCode($exitCode);
        } else {
            $this->expectException(HTTPResponse_Exception::class);
            $this->expectExceptionCode(404);
        }
        $controller = new PolyCommandController($polyCommand);

        $request = new HTTPRequest('GET', '', $params);
        $request->setSession(new Session([]));
        $response = $controller->handleRequest($request);

        if ($exitCode === 0) {
            $statusCode = 200;
        } elseif ($exitCode === 1) {
            $statusCode = 500;
        } elseif ($exitCode === 2) {
            $statusCode = 400;
        } else {
            $statusCode = $exitCode;
        }

        if ($allowed) {
            $this->assertSame($expectedOutput, $response->getBody());
        } else {
            // The 404 response will NOT contain any output from the command, because the command didn't run.
            $this->assertNotSame($expectedOutput, $response->getBody());
        }
        $this->assertSame($statusCode, $response->getStatusCode());
    }
}
