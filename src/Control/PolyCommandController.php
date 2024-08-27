<?php

namespace SilverStripe\Control;

use SilverStripe\PolyExecution\PolyCommand;
use SilverStripe\PolyExecution\HttpRequestInput;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Controller that allows routing HTTP requests to PolyCommands
 *
 * This controller is automatically wrapped around any PolyCommand
 * that is added to the regular routing configuration.
 */
class PolyCommandController extends Controller
{
    private PolyCommand $command;

    public function __construct(PolyCommand $polyCommand)
    {
        $this->command = $polyCommand;
        parent::__construct();
    }

    protected function init()
    {
        parent::init();
        if (!$this->command::canRunInBrowser()) {
            $this->httpError(404);
        }
    }

    public function index(HTTPRequest $request): HTTPResponse
    {
        $response = $this->getResponse();

        try {
            $input = HttpRequestInput::create($request, $this->command->getOptions());
        } catch (InvalidOptionException|InvalidArgumentException $e) {
            $response->setBody($e->getMessage());
            $response->setStatusCode(400);
            $this->afterHandleRequest();
            return $this->getResponse();
        }

        $buffer = new BufferedOutput();
        $output = PolyOutput::create(PolyOutput::FORMAT_HTML, $input->getVerbosity(), true, $buffer);
        $exitCode = $this->command->run($input, $output);
        $response->setBody($buffer->fetch());
        $responseCode = match (true) {
            $exitCode === Command::SUCCESS => 200,
            $exitCode === Command::FAILURE => 500,
            $exitCode === Command::INVALID => 400,
            // If someone's using an unexpected exit code, we shouldn't guess what they meant,
            // just assume they intentionally set it to something meaningful.
            default => $exitCode,
        };
        $response->setStatusCode($responseCode);
        return $this->getResponse();
    }
}
