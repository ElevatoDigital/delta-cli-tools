<?php

namespace DeltaCli\Script;

use DeltaCli\Environment;
use DeltaCli\Exception\InvalidOptions;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\Script as ScriptStep;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;

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
            InputArgument::REQUIRED,
            'The environment you want to copy the database from.'
        );

        $this->addSetterArgument(
            'destination-environment',
            InputArgument::REQUIRED,
            'The environment you want to copy the database to.'
        );

        parent::configure();
    }

    public function setSourceEnvironment($sourceEnvironment)
    {
        $this->sourceEnvironment = $this->getProject()->getTunneledEnvironment($sourceEnvironment);

        return $this;
    }

    public function setDestinationEnvironment($destinationEnvironment)
    {
        $this->destinationEnvironment = $this->getProject()->getTunneledEnvironment($destinationEnvironment);

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
            ->setEnvironment($this->destinationEnvironment)
            ->setDefinition(new InputDefinition());

        $restoreInput = new ArrayInput([]);
        $restoreInput->setInteractive(false);

        $this
            ->addStep($dumpScript)
            ->addStep(
                'assign-dump-file-to-restore-script',
                function () use ($dumpScript, $restoreScript) {
                    $restoreScript->setDumpFile($dumpScript->getDumpFile());
                }
            )
            ->addStep($restoreScript)
            ->addStep(
                'restore-to-destination-environment',
                new ScriptStep(
                    $restoreScript,
                    $restoreInput,
                    $this->getProject()->getOutput()
                )
            );
    }
}
