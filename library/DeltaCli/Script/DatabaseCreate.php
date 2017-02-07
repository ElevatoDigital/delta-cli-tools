<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class DatabaseCreate extends Script
{
    /**
     * @var string
     */
    private $type;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:create',
            'Create a new database.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();

        $this->addSetterArgument(
            'type',
            InputArgument::REQUIRED,
            'The type of database.  Either "mysql" or "postgres".'
        );

        parent::configure();
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    protected function addSteps()
    {
        $createStep = $this->getProject()->createDatabase($this->type);

        $this
            ->addStep($createStep)
            ->addStep(
                'display-resources',
                function () use ($createStep) {
                    return $this->getProject()->displayEnvironmentResources()
                        ->setSelectedEnvironment($createStep->getEnvironment())
                        ->run();
                }
            );
    }
}