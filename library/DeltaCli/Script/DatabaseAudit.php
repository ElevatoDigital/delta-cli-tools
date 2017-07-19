<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class DatabaseAudit extends Script
{
    /**
     * @var Script\Step\FindDatabases
     */
    private $findDbsStep;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:audit',
            'Audit a database for common tracking columns.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();

        $this->findDbsStep = $this->getProject()->findDatabases();
        $this->findDbsStep->configure($this->getDefinition());

        parent::configure();
    }

    protected function addSteps()
    {
        $this
            ->addStep($this->findDbsStep)
            ->addStep(
                'perform-database-audit',
                function () {
                    $database = $this->findDbsStep->getSelectedDatabase($this->getProject()->getInput());
                    $step     = $this->getProject()->performDatabaseAudit($database);
                    $step->setSelectedEnvironment($this->getEnvironment());
                    return $step->run();
                }
            );
    }
}
