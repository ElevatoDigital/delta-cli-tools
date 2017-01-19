<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class DatabaseRestore extends Script
{
    private $dumpFile;

    /**
     * @var string
     */
    private $databaseOptionName = 'database';

    /**
     * @var string
     */
    private $databaseTypeOptionName = 'database-type';


    /**
     * @var Script\Step\FindDatabases
     */
    private $findDbsStep;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:restore',
            'Restore from a database dump.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();

        $this->addSetterArgument(
            'dump-file',
            InputArgument::REQUIRED,
            'The dump file you want to restore.'
        );

        $this->findDbsStep = $this->getProject()->findDatabases();
        $this->findDbsStep->configure($this->getDefinition());

        parent::configure();
    }

    public function setDumpFile($dumpFile)
    {
        $this->dumpFile = $dumpFile;

        return $this;
    }

    public function setDatabaseOptionName($databaseOptionName)
    {
        $this->databaseOptionName = $databaseOptionName;

        return $this;
    }

    public function setDatabaseTypeOptionName($databaseTypeOptionName)
    {
        $this->databaseTypeOptionName = $databaseTypeOptionName;

        return $this;
    }

    protected function addSteps()
    {
        $this
            ->addStep($this->findDbsStep)
            ->addStep($this->getProject()->logAndSendNotifications())
            ->addStep(
                $this->getProject()->sanityCheckPotentiallyDangerousOperation(
                    'Restore a database from a dump file.'
                )
            )
            ->addStep(
                'backup-database-prior-to-restore',
                function () {
                    $database = $this->findDbsStep->getSelectedDatabase(
                        $this->getProject()->getInput(),
                        $this->databaseOptionName,
                        $this->databaseTypeOptionName
                    );
                    $dumpStep = $this->getProject()->dumpDatabase($database);
                    $dumpStep->setSelectedEnvironment($this->getEnvironment());
                    return $dumpStep->run();
                }
            )
            ->addStep(
                'empty-database-prior-to-restore',
                function () {
                    $database = $this->findDbsStep->getSelectedDatabase(
                        $this->getProject()->getInput(),
                        $this->databaseOptionName,
                        $this->databaseTypeOptionName
                    );

                    $emptyStep = $this->getProject()->emptyDatabase($database);
                    $emptyStep->setSelectedEnvironment($this->getEnvironment());
                    return $emptyStep->run();
                }
            )
            ->addStep(
                'restore-database-from-dump-file',
                function () {
                    $database = $this->findDbsStep->getSelectedDatabase(
                        $this->getProject()->getInput(),
                        $this->databaseOptionName,
                        $this->databaseTypeOptionName
                    );

                    $restoreStep = $this->getProject()->restoreDatabase($database, $this->dumpFile);
                    $restoreStep->setSelectedEnvironment($this->getEnvironment());
                    return $restoreStep->run();
                }
            );
    }
}
