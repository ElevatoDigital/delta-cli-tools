<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Console\Output\Spinner;
use DeltaCli\Host;
use DeltaCli\Log\Detector\DetectorSet;
use DeltaCli\Log\LogInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(DetectorSet $detectorSet, OutputInterface $output)
    {
        $this->detectorSet = $detectorSet;
        $this->output      = $output;
    }

    public function runOnHost(Host $host)
    {
        $spinner = new Spinner($this->output);

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
