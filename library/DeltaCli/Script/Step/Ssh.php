<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Exec;
use DeltaCli\Host;

class Ssh extends EnvironmentHostsStepAbstract
{
    /**
     * @var string
     */
    private $command;

    public function __construct($command)
    {
        $this->command = $command;
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
        $tunnel = $host->getSshTunnel();
        $tunnel->setUp();
        Exec::run($tunnel->assembleSshCommand($this->command), $output, $exitStatus);
        $tunnel->tearDown();

        return [$output, $exitStatus];
    }
}
