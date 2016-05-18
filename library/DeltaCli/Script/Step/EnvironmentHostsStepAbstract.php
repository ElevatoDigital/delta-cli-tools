<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Exception\EnvironmentNotAvailableForStep;
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
        if (!$this->environment) {
            throw new EnvironmentNotAvailableForStep();
        }
        
        $output        = [];
        $verboseOutput = [];

        $failedHosts        = [];
        $misconfiguredHosts = [];

        /* @var $host Host */
        foreach ($this->environment->getHosts() as $host) {
            if (!$host->hasRequirementsForSshUse()) {
                $misconfiguredHosts[] = $host;
                continue;
            }

            $hostResult = $this->runOnHost($host);

            if (3 === count($hostResult)) {
                list($hostOutput, $exitStatus, $verboseHostOutput) = $hostResult;
            } else {
                list($hostOutput, $exitStatus) = $hostResult;
                $verboseHostOutput = [];
            }

            if ($exitStatus) {
                $failedHosts[] = $host;
            }

            if (count($hostOutput)) {
                $output[] = '<comment>' . $host->getHostname() . '</comment>';

                foreach ($hostOutput as $line) {
                    $output[] = '  ' . $line;
                }

                foreach ($verboseHostOutput as $line) {
                    $verboseOutput[] = '  ' . $line;
                }
            }
        }

        return $this->generateResult($failedHosts, $misconfiguredHosts, $output, $verboseOutput);
    }

    protected function generateResult(array $failedHosts, array $misconfiguredHosts, array $output, array $verboseOutput)
    {
        if (count($this->environment->getHosts()) && !count($failedHosts) && !count($misconfiguredHosts)) {
            $result = new Result($this, Result::SUCCESS, $output);
            $result->setExplanation($this->getSuccessfulResultExplanation($this->environment->getHosts()));
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

        $result->setVerboseOutput($verboseOutput);

        return $result;
    }

    protected function getSuccessfulResultExplanation(array $hosts)
    {
        if (1 !== count($hosts)) {
            return sprintf('on all %d hosts', count($hosts));
        } else {
            /* @var $host Host */
            $host = current($hosts);
            return sprintf('on %s', $host->getHostname());
        }
    }
}
