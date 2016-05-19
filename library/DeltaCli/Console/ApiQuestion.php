<?php

namespace DeltaCli\Console;

use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ApiQuestion
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $this->input          = $input;
        $this->output         = $output;
        $this->questionHelper = $questionHelper;
    }

    public function ask($question)
    {
        return $this->questionHelper->ask(
            $this->input,
            $this->output,
            new Question("<question>{$question}</question>\n")
        );
    }

    public function askHiddenQuestion($question)
    {
        $question = new Question("<question>{$question}</question>\n");
        $question->setHidden(true);
        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    public function responseIsSuccessful(Response $response)
    {
        if (200 === $response->getStatusCode()) {
            return true;
        } else {
            $this->handleUnsuccessfulResponse($response);
            return false;
        }
    }

    public function handleUnsuccessfulResponse(Response $response)
    {
        if ('application/json' !== $response->getHeaderLine('Content-Type')) {
            $this->output->writeln("<error>Invalid API response returned: {$response->getReasonPhrase()}.</error>");
        } else {
            $responseDetails = json_decode($response->getBody(), true);

            if (isset($responseDetails['message']) && isset($responseDetails['code'])) {
                $this->output->writeln("<error>{$responseDetails['message']} ({$responseDetails['code']})</error>");
            } else {
                $this->output->writeln(
                    "<error>Delta API response didn't have the expected message and code fields.</error>"
                );
            }
        }

        $this->output->writeln('');

        return $this;
    }

    public function getResponseField(Response $response, $fieldName)
    {
        $responseDetails = json_decode($response->getBody(), true);
        return $responseDetails[$fieldName];
    }
}
