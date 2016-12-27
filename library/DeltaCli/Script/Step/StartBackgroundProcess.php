<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Host;

class StartBackgroundProcess extends EnvironmentHostsStepAbstract
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $outputFile = '/dev/null';

    /**
     * @var string
     */
    private $errorFile = '/dev/null';

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();

        $tunnel->setUp();

        $this->exec(
            $tunnel->assembleSshCommand(
                sprintf(
                    'nohup bash -c %s > %s 2> %s < /dev/null &',
                    escapeshellarg($this->command),
                    escapeshellarg($this->outputFile),
                    escapeshellarg($this->errorFile)
                )
            ),
            $output,
            $exitStatus
        );

        $tunnel->tearDown();

        return [$output, $exitStatus];
    }

    public function getName()
    {
        return 'start-background-process';
    }

    public function setOutputFile($outputFile)
    {
        $this->outputFile = $outputFile;

        return $this;
    }

    public function setErrorFile($errorFile)
    {
        $this->errorFile = $errorFile;

        return $this;
    }
}
