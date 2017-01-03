<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class DatabaseDump extends Script
{
    /**
     * @var string
     */
    private $dumpFile;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:dump',
            'Dump a database from a remote environment.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();

        parent::configure();
    }

    public function getDumpFile()
    {
        return $this->dumpFile;
    }

    protected function addSteps()
    {
        $findDbsStep = $this->getProject()->findDatabases();

        $this
            ->addStep($findDbsStep)
            ->addStep($this->getProject()->logAndSendNotifications())
            ->addStep(
                'dump-database',
                function () use ($findDbsStep) {
                    $dumpStep = $this->getProject()->dumpDatabase(reset($findDbsStep->getDatabases()));
                    $dumpStep->setSelectedEnvironment($this->getProject()->getSelectedEnvironment());
                    $result = $dumpStep->run();
                    $this->dumpFile = $dumpStep->getDumpFileName();
                    return $result;
                }
            );
    }
}
