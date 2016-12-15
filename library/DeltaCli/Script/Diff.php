<?php

namespace DeltaCli\Script;

use DeltaCli\FileTransferPaths;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\Rsync as RsyncStep;
use Symfony\Component\Console\Input\InputOption;

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
     * @var array
     */
    private $excludes = [];

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

    public function setExclude(array $excludes)
    {
        $this->excludes = $excludes;

        return $this;
    }

    protected function configure()
    {
        // We want temporary files to be removed even if diff "fails"
        $this->setStopOnFailure(false);

        $this->addSetterArgument('file1');
        $this->addSetterArgument('file2');

        $this->addSetterOption('exclude', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL);

        parent::configure();
    }

    protected function addSteps()
    {
        $paths = new FileTransferPaths($this->getProject(), $this->file1, $this->file2);
        $rsync = new RsyncStep($this->getTemporaryLocalPath(), $paths->getRemotePath(), FileTransferPaths::DOWN);
        $env   = $this->getProject()->getTunneledEnvironment($paths->getRemoteEnvironment());
        $this->setEnvironment($env);

        if (count($this->excludes)) {
            foreach ($this->excludes as $exclude) {
                $rsync->exclude($exclude);
            }
        }

        $this->addStep($rsync)
            ->setName('copy-remote-files');

        $this->addStep(
            'generate-diff',
            sprintf(
                'diff --new-file --recursive --unified --ignore-all-space %s %s',
                escapeshellarg($this->getTemporaryLocalPath()),
                escapeshellarg($paths->getLocalPath())
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
