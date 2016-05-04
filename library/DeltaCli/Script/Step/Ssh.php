<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Host;

class Ssh extends EnvironmentHostsStepAbstract
{
    const INCLUDE_APPLICATION_ENV = true;

    const OMIT_APPLICATION_ENV = false;

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

    public function runOnHost(Host $host)
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
            'ssh %s -p %s %s@%s %s 2>&1',
            ($host->getSshPrivateKey() ? '-i ' . escapeshellarg($host->getSshPrivateKey()) : ''),
            escapeshellarg($host->getSshPort()),
            escapeshellarg($host->getUsername()),
            escapeshellarg($host->getHostname()),
            escapeshellarg($sshCommand)
        );

        exec($command, $output, $exitStatus);

        return [$output, $exitStatus];
    }
}
