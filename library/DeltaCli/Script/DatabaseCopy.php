<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

class DatabaseList extends Script
{
    private $sourceEnvironment;

    private $destinationEnvironment;

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
        $this->addSetterArgument(
            'source-environment',
            null,
            'The environment from which you want to copy.'
        );

        $this->addSetterArgument(
            'destination-environment',
            null,
            'The environment to which you want to copy.'
        );

        parent::configure();
    }

    public function setSourceEnvironment($sourceEnvironment)
    {
        $this->sourceEnvironment = $this->getProject()->getEnvironment($sourceEnvironment);

        return $this;
    }

    public function setDestinationEnvironment($destinationEnvironment)
    {
        $this->destinationEnvironment = $this->getProject()->getEnvironment($destinationEnvironment);
    }

    protected function addSteps()
    {
        $findDbsStep = $this->getProject()->findDatabases();

        $this
            ->addStep($findDbsStep);
    }
}
