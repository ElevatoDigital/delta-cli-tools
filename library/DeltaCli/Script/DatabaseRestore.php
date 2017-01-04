<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class DatabaseRestore extends Script
{
    private $dumpFile;

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

        parent::configure();
    }

    public function setDumpFile($dumpFile)
    {
        $this->dumpFile = $dumpFile;

        return $this;
    }

    protected function addSteps()
    {
        $findDbsStep = $this->getProject()->findDatabases();

        $this
            ->addStep($findDbsStep)
            ->addStep($this->getProject()->logAndSendNotifications())
            ->addStep(
                'backup-database-prior-to-restore',
                function () use ($findDbsStep) {
                    $database = reset($findDbsStep->getDatabases());
                    $dumpStep = $this->getProject()->dumpDatabase($database);
                    $dumpStep->setSelectedEnvironment($this->getEnvironment());
                    return $dumpStep->run();
                }
            )
            ->addStep(
                'empty-database-prior-to-restore',
                function () use ($findDbsStep) {
                    $database  = reset($findDbsStep->getDatabases());
                    $emptyStep = $this->getProject()->emptyDatabase($database);
                    $emptyStep->setSelectedEnvironment($this->getEnvironment());
                    return $emptyStep->run();
                }
            )
            ->addStep(
                'restore-database-from-dump-file',
                function () use ($findDbsStep) {
                    $restoreStep = $this->getProject()->restoreDatabase(
                        reset($findDbsStep->getDatabases()),
                        $this->dumpFile
                    );

                    $restoreStep->setSelectedEnvironment($this->getEnvironment());

                    return $restoreStep->run();
                }
            );
    }
}
