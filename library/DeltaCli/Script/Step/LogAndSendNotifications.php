<?php

namespace DeltaCli\Script\Step;

use DeltaCli\ApiClient;
use DeltaCli\Console\ApiQuestion;
use DeltaCli\Project;
use DeltaCli\Script as ScriptObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class LogAndSendNotifications extends StepAbstract
{
    /**
     * @var Project
     */
    private $project;

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

    public function __construct(Project $project, ApiClient $apiClient = null)
    {
        $this->project   = $project;
        $this->input     = $project->getInput();
        $this->output    = $project->getOutput();
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
        if (!$this->apiClient->hasAccountKey()) {
            $this->runAccountKeyWorkflow($script);
        }

        if (!$this->apiClient->hasProjectKey()) {
            $this->runProjectKeyWorkflow($script);
        }
    }

    public function run()
    {
        $response = $this->apiClient->getProject($this->apiClient->getProjectKey());

        if (200 === $response->getStatusCode()) {
            $result = new Result($this, Result::SUCCESS);
            $result->setStatusMessage('is ready to run at the end of this script');
        } else {
            if ('application/json' !== $response->getHeaderLine('Content-Type')) {
                $output = $response->getReasonPhrase();
            } else {
                $json   = json_decode($response->getBody(), true);
                $output = sprintf('%s (%s)', $json['message'], $json['code']);
            }

            $result = new Result($this, Result::FAILURE, $output);
            $result->setExplanation('because there was a problem communicating with Delta API');
        }

        return $result;
    }

    public function postRun(ScriptObject $script)
    {
        $this->output->writeln('<comment>Logging and sending notifications via Delta API...</comment>');

        $response = $this->apiClient->postResults($script->getApiResults());

        if (200 === $response->getStatusCode()) {
            $this->output->writeln('<info>Successfully logged results and sent notifications.</info>');
        } else {
            $this->output->writeln('<error>There was an error sending the results to the Delta API</error>');

            if ('application/json' !== $response->getHeaderLine('Content-Type')) {
                $this->output->writeln('  ' . $response->getReasonPhrase());
            } else {
                $json = json_decode($response->getBody(), true);
                $this->output->writeln(sprintf('  %s (%s)', $json['message'], $json['code']));
            }
        }
    }

    private function runAccountKeyWorkflow(ScriptObject $script)
    {
        /* @var $questionHelper \Symfony\Component\Console\Helper\QuestionHelper */
        $questionHelper = $script->getHelperSet()->get('question');

        $this->output->writeln(
            '<comment>We could not find your Delta API key, which is needed for logging and notifications.</comment>',
            ''
        );

        $question = new ChoiceQuestion(
            '<question>Do you have a Delta API account?</question>',
            [
                'y' => 'I already have an account.',
                'n' => 'I need to sign up for a Delta API account.'
            ]
        );

        $selected = $questionHelper->ask($this->input, $this->output, $question);

        if ('y' === $selected) {
            $this->runLoginWorkflow($script);
        } else {
            $this->runSignUpWorkflow($script);
        }
    }

    private function runLoginWorkflow(ScriptObject $script)
    {
        /* @var $questionHelper \Symfony\Component\Console\Helper\QuestionHelper */
        $questionHelper = $script->getHelperSet()->get('question');
        $loginQuestion  = new ApiQuestion($this->input, $this->output, $questionHelper);

        while (1) {
            $emailAddress = $loginQuestion->ask('What is your email address?');
            $password     = $loginQuestion->askHiddenQuestion('What is your password?');
            $response     = $this->apiClient->login($emailAddress, $password);

            if ($loginQuestion->responseIsSuccessful($response)) {
                $this->apiClient->writeAccountKey($loginQuestion->getResponseField($response, 'api_key'));

                $this->output->writeln(
                    [
                        '<info>Successfully logged into your Delta API account.  Your API key has been</info>',
                        '<info>written to the .delta-api.json file in your home folder.</info>'
                    ]
                );

                break;
            }
        }
    }

    private function runSignUpWorkflow(ScriptObject $script)
    {
        /* @var $questionHelper \Symfony\Component\Console\Helper\QuestionHelper */
        $questionHelper = $script->getHelperSet()->get('question');

        $emailAddressQuestion = new ApiQuestion($this->input, $this->output, $questionHelper);

        while (1) {
            $emailAddress = $emailAddressQuestion->ask('What is your email address?');
            $response     = $this->apiClient->signUpWithEmail($emailAddress);

            if ($emailAddressQuestion->responseIsSuccessful($response)) {
                $this->output->writeln(
                    [
                        '<info>An email has been sent to your address with a sign-up authorization code.</info>',
                        '<info>Enter the code below to continue with the account creation process.</info>'
                    ]
                );

                break;
            }
        }

        $accountQuestion = new ApiQuestion($this->input, $this->output, $questionHelper);

        while (1) {
            $authorizationCode = $accountQuestion->ask('What is the authorization code you received?');

            $password = $accountQuestion->askHiddenQuestion(
                'What would you like to use as your password? (12 character minimum)'
            );

            $confirmPassword = $accountQuestion->askHiddenQuestion('Repeat your password to confirm.');

            $response = $this->apiClient->createAccount($authorizationCode, $password, $confirmPassword);

            if ($accountQuestion->responseIsSuccessful($response)) {
                $apiKey = $accountQuestion->getResponseField($response, 'api-key');

                $this->apiClient->writeAccountKey($apiKey);

                $this->output->writeln(
                    [
                        'Your Delta API has been successfully created!',
                        'Your API key is:',
                        '',
                        '  ' . $apiKey,
                        '',
                        'It has been written to the .delta-api.json file in your home folder.'
                    ]
                );

                break;
            }
        }
    }

    private function runProjectKeyWorkflow(ScriptObject $script)
    {
        /* @var $questionHelper \Symfony\Component\Console\Helper\QuestionHelper */
        $questionHelper = $script->getHelperSet()->get('question');

        $this->output->writeln(
            '<comment>This project does not appear to have a Delta API key.  Creating one now...</comment>',
            ''
        );

        $projectQuestion = new ApiQuestion($this->input, $this->output, $questionHelper);
        $retryInterval   = 3;

        while (!$this->apiClient->hasProjectKey()) {
            $response = $this->apiClient->createProject($this->project->getName());

            if ($projectQuestion->responseIsSuccessful($response)) {
                $this->apiClient->writeProjectKey($projectQuestion->getResponseField($response, 'api_key'));

                $this->output->writeln(
                    [
                        '<info>Successfully created project API key and saved it to delta-api.json.</info>',
                        '<info>Please commit this file to git to avoid adding duplicates to Delta API.</info>'
                    ]
                );
            } else {
                $this->output->writeln(
                    "<error>Retrying in {$retryInterval} seconds...</error>"
                );

                sleep($retryInterval);

                $retryInterval += 2;
            }
        }
    }
}
