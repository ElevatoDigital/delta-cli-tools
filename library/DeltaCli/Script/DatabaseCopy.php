<?php

namespace DeltaCli\Script;

use DeltaCli\Environment;
use DeltaCli\Exception\InvalidOptions;
use DeltaCli\Project;
use DeltaCli\Script;

class DatabaseCopy extends Script
{
    /**
     * @var Environment
     */
    private $sourceEnvironment;

    /**
     * @var Environment
     */
    private $destinationEnvironment;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:copy',
            'Copy a database from one environment to another.'
        );
    }

    protected function configure()
    {
        $this->addSetterArgument(
            'source-environment',
            null,
            'The environment you want to copy the database from.'
        );

        $this->addSetterArgument(
            'destination-environment',
            null,
            'The environment you want to copy the database to.'
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

        return $this;
    }

    protected function addSteps()
    {
        if ($this->sourceEnvironment->getName() === $this->destinationEnvironment->getName()) {
            throw new InvalidOptions('Cannot copy a database to and from the same environment.');
        }

        /* @var $dumpScript \DeltaCli\Script\DatabaseDump */
        $dumpScript = $this->getProject()->getScript('db:dump')
            ->setEnvironment($this->sourceEnvironment);

        /* @var $restoreScript \DeltaCli\Script\DatabaseRestore */
        $restoreScript = $this->getProject()->getScript('db:restore')
            ->setEnvironment($this->destinationEnvironment);

        $this
            ->addStep($dumpScript)
            ->addStep(
                'restore-to-destination-environment',
                function () use ($dumpScript, $restoreScript) {
                    $restoreScript->setDumpFile($dumpScript->getDumpFile());
                    return $restoreScript->run($this->getProject()->getInput(), $this->getProject()->getOutput());
                }
            );
    }
}
