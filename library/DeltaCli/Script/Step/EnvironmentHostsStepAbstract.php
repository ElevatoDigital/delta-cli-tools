<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Host;

abstract class EnvironmentHostsStepAbstract extends StepAbstract implements EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    protected $environment;

    abstract public function runOnHost(Host $host);

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function run()
    {
        return $this->runOnAllHosts();
    }

    protected function runOnAllHosts()
    {
        $output = [];

        $failedHosts        = [];
        $misconfiguredHosts = [];

        /* @var $host Host */
        foreach ($this->environment->getHosts() as $host) {
            if (!$host->hasRequirementsForSshUse()) {
                $misconfiguredHosts[] = $host;
                continue;
            }

            list($hostOutput, $exitStatus) = $this->runOnHost($host);

            if ($exitStatus) {
                $failedHosts[] = $host;
            }

            $output[] = $host->getHostname();

            foreach ($hostOutput as $line) {
                $output[] = '  ' . $line;
            }
        }

        return $this->generateResult($failedHosts, $misconfiguredHosts, $output);
    }

    protected function generateResult(array $failedHosts, array $misconfiguredHosts, array $output)
    {
        if (count($this->environment->getHosts()) && !count($failedHosts) && !count($misconfiguredHosts)) {
            $result = new Result($this, Result::SUCCESS, $output);
            $result->setExplanation('on all ' . count($this->environment->getHosts()) . ' host(s)');
        } else {
            $result = new Result($this, Result::FAILURE, $output);

            if (!count($this->environment->getHosts())) {
                $result->setExplanation('because no hosts were added in the environment');
            } else {
                $explanations = [];

                if (count($failedHosts)) {
                    $explanations[] = count($failedHosts) . ' host(s) failed';
                }

                if (count($misconfiguredHosts)) {
                    $explanations[] = count($misconfiguredHosts) . ' host(s) were not configured for SSH';
                }

                $result->setExplanation('because ' . implode(' and ', $explanations));
            }
        }

        return $result;
    }
}
