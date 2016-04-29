<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Host;

class Ssh extends StepAbstract implements EnvironmentAwareInterface
{
    const INCLUDE_APPLICATION_ENV = true;

    const OMIT_APPLICATION_ENV = false;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $command;

    /**
     * @var bool
     */
    private $includeApplicationEnv = self::INCLUDE_APPLICATION_ENV;

    public function __construct($command, $includeApplicationEnv = null)
    {
        if (null === $includeApplicationEnv) {
            $includeApplicationEnv = self::INCLUDE_APPLICATION_ENV;
        }

        $this->command               = $command;
        $this->includeApplicationEnv = $includeApplicationEnv;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return $this->command . ' over SSH';
        }
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function run()
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

            list($hostOutput, $exitStatus) = $this->ssh($host);

            if ($exitStatus) {
                $failedHosts[] = $host;
            }

            $output[] = $host->getHostname();

            foreach ($hostOutput as $line) {
                $output[] = '  ' . $line;
            }
        }

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

    private function ssh(Host $host)
    {
        $sshCommand = $this->command;

        if ($this->includeApplicationEnv) {
            $sshCommand = sprintf(
                'APPLICATION_ENV=%s %s',
                escapeshellarg($this->environment->getName()),
                $this->command
            );
        }

        $command = sprintf(
            'ssh -i %s %s@%s %s 2>&1',
            escapeshellarg($host->getSshPrivateKey()),
            escapeshellarg($host->getUsername()),
            escapeshellarg($host->getHostname()),
            escapeshellarg($sshCommand)
        );

        exec($command, $output, $exitStatus);

        return [$output, $exitStatus];
    }
}
