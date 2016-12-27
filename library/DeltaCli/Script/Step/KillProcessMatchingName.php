<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Host;
use Cocur\Slugify\Slugify;

class KillProcessMatchingName extends EnvironmentHostsStepAbstract
{
    private $searchString;

    public function __construct($searchString)
    {
        $this->searchString = $searchString;
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();

        $tunnel->setUp();

        $this->exec(
            $tunnel->assembleSshCommand(
                sprintf(
                    "ps aux | grep %s | grep -v grep | awk '{print $2}' | xargs kill 2>&1",
                    escapeshellarg($this->searchString)
                )
            ),
            $output,
            $exitStatus
        );

        if (0 !== $exitStatus) {
            $output     = ["No process matching '{$this->searchString}' was running."];
            $exitStatus = 0;
        }

        $tunnel->tearDown();

        return [$output, $exitStatus];
    }

    public function getName()
    {
        $slugify = new Slugify();

        return 'kill-process-matching-' . $slugify->slugify($this->searchString);
    }

}
