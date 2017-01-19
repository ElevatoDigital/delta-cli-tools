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
use Symfony\Component\Console\Input\InputOption;
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

        $this->addOption('database', null, InputOption::VALUE_REQUIRED, 'The database you want to copy from.');

        $this->addOption(
            'database-type',
            null,
            InputOption::VALUE_REQUIRED,
            'The type of database you want to copy from.'
        );

        $this->addOption('restore-database', null, InputOption::VALUE_REQUIRED, 'The database you want to restore to.');

        $this->addOption(
            'restore-database-type',
            null,
            InputOption::VALUE_REQUIRED,
            'The type of database you want to restore to.'
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
        /* @var $dumpScript \DeltaCli\Script\DatabaseDump */
        $dumpScript = $this->getProject()->getScript('db:dump');

        /* @var $restoreScript \DeltaCli\Script\DatabaseRestore */
        $restoreScript = $this->getProject()->getScript('db:restore');

        $this
            ->addStep(
                'configure-environments',
                function () use ($dumpScript, $restoreScript) {
                    if ($this->sourceEnvironment->getName() === $this->destinationEnvironment->getName()) {
                        throw new InvalidOptions('Cannot copy a database to and from the same environment.');
                    }

                    $dumpScript->setEnvironment($this->sourceEnvironment);
                    $restoreScript->setEnvironment($this->destinationEnvironment);

                    $restoreScript
                        ->setDatabaseOptionName('restore-database')
                        ->setDatabaseTypeOptionName('restore-database-type');
                }
            )
            ->addStep($dumpScript)
            ->addStep(
                'assign-dump-file-to-restore-script',
                function () use ($dumpScript, $restoreScript) {
                    $restoreScript->setDumpFile($dumpScript->getDumpFile());
                }
            )
            ->addStep($restoreScript);
    }
}
