<?php

namespace DeltaCli\Script\Step;

use DeltaCli\ApiClient;
use DeltaCli\Script as ScriptObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class LogAndSendNotifications extends StepAbstract
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
     * @var ApiClient
     */
    private $apiClient;

    public function __construct(InputInterface $input, OutputInterface $output, ApiClient $apiClient = null)
    {
        $this->input     = $input;
        $this->output    = $output;
        $this->apiClient = ($apiClient ?: new ApiClient());
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return 'log-and-send-notifications';
        }
    }

    public function preRun(ScriptObject $script)
    {
        while (!$this->apiClient->hasAccountKey()) {
            /* @var $questionHelper \Symfony\Component\Console\Helper\QuestionHelper */
            $questionHelper = $script->getHelperSet()->get('question');

            $emailAddress = $questionHelper->ask(
                $this->input,
                $this->output,
                new Question("<question>What is your email address?</question>\n")
            );

            $response = $this->apiClient->signUpWithEmail($emailAddress);

            if (200 === $response->getStatusCode()) {
                $this->output->writeln(
                    [
                        '<info>An email has been sent to your address with a sign-up authorization code.</info>',
                        '<info>Enter the code below to continue with account creation process.</info>'
                    ]
                );

                break;
            } else {
                $responseDetails = json_decode($response->getBody(), true);

                $this->output->writeln(
                    [
                        "<error>{$responseDetails['message']} ({$responseDetails['code']})</error>",
                        ''
                    ]
                );
            }
        }
    }

    public function run()
    {
    }
}
