<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class DatabaseDiagram extends Script
{
    /**
     * @var Script\Step\FindDatabases
     */
    private $findDbsStep;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:diagram',
            'Generate a DB diagram using Graphviz.'
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
                'generate-diagram',
                function () {
                    $hosts = $this->getEnvironment()->getHosts();
                    $host  = reset($hosts);

                    $database = $this->findDbsStep->getSelectedDatabase($this->getProject()->getInput());
                    $database->setSshTunnel($host->getSshTunnel());
                    return $this->getProject()->generateDatabaseDiagram($database)->run();
                }
            );
    }
}
