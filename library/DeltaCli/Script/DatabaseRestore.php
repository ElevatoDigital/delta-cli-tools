<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;

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
            null,
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
                    $dumpStep = $this->getProject()->dumpDatabase(reset($findDbsStep->getDatabases()));
                    $dumpStep->setSelectedEnvironment($this->getProject()->getSelectedEnvironment());
                    return $dumpStep->run();
                }
            )
            ->addStep(
                'empty-database-prior-to-restore',
                function () use ($findDbsStep) {

                }
            )
            ->addStep(
                'restore-database-from-dump-file',
                function () use ($findDbsStep) {
                    $restoreStep = $this->getProject()->restoreDatabase(
                        reset($findDbsStep->getDatabases()),
                        $this->dumpFile
                    );

                    $restoreStep->setSelectedEnvironment($this->getProject()->getSelectedEnvironment());

                    return $restoreStep->run();
                }
            );
    }
}
