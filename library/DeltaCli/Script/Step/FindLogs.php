<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Console\Output\Spinner;
use DeltaCli\Host;
use DeltaCli\Log\Detector\DetectorSet;
use DeltaCli\Log\LogInterface;

class FindLogs extends EnvironmentHostsStepAbstract
{
    /**
     * @var DetectorSet
     */
    private $detectorSet;

    /**
     * @var LogInterface[]
     */
    private $logs = [];

    public function __construct(DetectorSet $detectorSet)
    {
        $this->detectorSet = $detectorSet;
    }

    public function runOnHost(Host $host)
    {
        $spinner = Spinner::forStep($this);

        /* @var $detector \DeltaCli\Log\Detector\DetectorInterface */
        foreach ($this->detectorSet->getAll() as $detector) {
            $spinner->spin("Looking for {$detector->getName()}...");

            $log = $detector->detectLogOnHost($host);

            if (false !== $log) {
                $this->logs[] = $log;
            }
        }

        $spinner->clear();
    }

    public function getName()
    {
        return 'find-logs';
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
