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

    /**
     * @var Script\Step\FindDatabases
     */
    private $findDbsStep;

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

        $this->findDbsStep = $this->getProject()->findDatabases();
        $this->findDbsStep->configure($this->getDefinition());

        parent::configure();
    }

    public function getDumpFile()
    {
        return $this->dumpFile;
    }

    protected function addSteps()
    {
        $this
            ->addStep($this->findDbsStep)
            ->addStep($this->getProject()->logAndSendNotifications())
            ->addStep(
                'dump-database',
                function () {
                    $database  = $this->findDbsStep->getSelectedDatabase($this->getProject()->getInput());
                    $dumpStep  = $this->getProject()->dumpDatabase($database);
                    $dumpStep->setSelectedEnvironment($this->getEnvironment());
                    $result = $dumpStep->run();
                    $this->dumpFile = $dumpStep->getDumpFileName();
                    return $result;
                }
            );
    }
}
