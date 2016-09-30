<?php

namespace DeltaCli\Script;

use DeltaCli\FileTransferPaths;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\Rsync as RsyncStep;

class Diff extends Script
{
    /**
     * @var string
     */
    private $file1;

    /**
     * @var string
     */
    private $file2;

    /**
     * @var string
     */
    private $temporaryLocalPath;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'diff',
            'Generate a diff between files in one environment versus another.'
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
        // We want temporary files to be removed even if diff "fails"
        $this->setStopOnFailure(false);

        $this->addSetterArgument('file1');
        $this->addSetterArgument('file2');

        parent::configure();
    }

    protected function addSteps()
    {
        $paths = new FileTransferPaths($this->getProject(), $this->file1, $this->file2);
        $rsync = new RsyncStep($this->getTemporaryLocalPath(), $paths->getRemotePath(), FileTransferPaths::DOWN);
        $env   = $this->getProject()->getEnvironment($paths->getRemoteEnvironment());
        $this->setEnvironment($env);

        $this->addStep($rsync)
            ->setName('copy-remote-files');

        $this->addStep(
            'generate-diff',
            sprintf(
                'diff -N -r -u %s %s',
                escapeshellarg($paths->getLocalPath()),
                escapeshellarg($this->getTemporaryLocalPath())
            )
        );

        $this->addStep(
            'remove-temporary-files',
            sprintf(
                'rm -rf %s',
                $this->getTemporaryLocalPath()
            )
        );
    }

    private function getTemporaryLocalPath()
    {
        if (!$this->temporaryLocalPath) {
            $this->temporaryLocalPath = sys_get_temp_dir() . '/delta-cli-diff-' . uniqid();
        }

        return $this->temporaryLocalPath;
    }
}
