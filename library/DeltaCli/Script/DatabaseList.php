<?php

namespace DeltaCli\Script;

use DeltaCli\Console\Output\DatabasesTable;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Helper\Table;

class DatabaseList extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:list',
            'Find and list databases on a remote environment.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();
        parent::configure();
    }

    protected function addSteps()
    {
        $findDbsStep = $this->getProject()->findDatabases();

        $this
            ->addStep($findDbsStep)
            ->addStep(
                'list-databases',
                function () use ($findDbsStep) {
                    $databasesTable = new DatabasesTable(
                        $this->getProject()->getOutput(),
                        $findDbsStep->getDatabases()
                    );

                    $databasesTable->render();
                }
            );
    }
}
