<?php

namespace DeltaCli\Script;

use DeltaCli\FileTransferPaths;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\Scp as ScpStep;

class Scp extends Script
{
    private $file1;

    private $file2;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'scp',
            'Use scp to transfer a file to or from an environment.'
        );
    }

    public function setFile1($file1)
    {
        $this->file1 = $file1;

        return $this;
    }

    public function setFile2($file2)
    {
        $this->file2 = $file2;

        return $this;
    }

    protected function configure()
    {
        $this->addSetterArgument('file1');
        $this->addSetterArgument('file2');

        parent::configure();
    }

    protected function addSteps()
    {
        $paths = new FileTransferPaths($this->getProject(), $this->file1, $this->file2);
        $scp   = new ScpStep($paths->getLocalPath(), $paths->getRemotePath(), $paths->getDirection());
        $env   = $this->getProject()->getEnvironment($paths->getRemoteEnvironment());
        $this->setEnvironment($env);
        $this->addStep($scp);
    }
}
