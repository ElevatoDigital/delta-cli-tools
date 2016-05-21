<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Script as ScriptObject;

class LogAndSendNotifications extends DeltaApiAbstract implements EnvironmentOptionalInterface
{
    /**
     * @var Environment
     */
    private $environment;

    private $sendNotificationsOnScriptSuccess = true;

    private $sendNotificationsOnScriptFailure = true;

    public function setSendNotificationsOnScriptSuccess($sendNotificationsOnScriptSuccess)
    {
        $this->sendNotificationsOnScriptSuccess = $sendNotificationsOnScriptSuccess;

        return $this;
    }

    public function setSendNotificationsOnceScriptFailure($sendNotificationsOnScriptFailure)
    {
        $this->sendNotificationsOnScriptFailure = $sendNotificationsOnScriptFailure;

        return $this;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return 'log-and-send-notifications';
        }
    }

    public function run()
    {
        if ($this->environment && !$this->environment->getLogAndSendNotifications()) {
            $result = new Result($this, Result::SKIPPED);

            if ($this->environment->isDevEnvironment()) {
                $result->setExplanation("because {$this->environment->getName()} is a dev environment");
            } else {
                $result->setExplanation("because {$this->environment->getName()} environment has them disabled");
            }
            return $result;
        }

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
        if ($this->environment && !$this->environment->getLogAndSendNotifications()) {
            return;
        }

        $this->output->writeln('<comment>Logging and sending notifications via Delta API...</comment>');

        $sendNotifications = $this->shouldSendNotifications($script->getApiResults()->getScriptResult());

        $response = $this->apiClient->postResults(
            $script->getApiResults(),
            $this->project,
            $sendNotifications
        );

        if (200 === $response->getStatusCode()) {
            if ($sendNotifications) {
                $this->output->writeln('<info>Successfully logged results and sent notifications.</info>');
            } else {
                $this->output->writeln('<info>Successfully logged results.</info>');
            }
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

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    private function shouldSendNotifications($scriptResult)
    {
        if (Result::SUCCESS === $scriptResult && $this->sendNotificationsOnScriptSuccess) {
            return true;
        } else if (Result::FAILURE === $scriptResult && $this->sendNotificationsOnScriptFailure) {
            return true;
        } else {
            return false;
        }
    }
}
