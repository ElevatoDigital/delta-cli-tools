<?php

namespace DeltaCli\Script;

use DeltaCli\FileTransferPaths;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\Rsync as RsyncStep;
use Symfony\Component\Console\Input\InputOption;

class Rsync extends Script
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
     * @var bool
     */
    private $delete = false;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'rsync',
            'Use rsync to synchronize a folder between one environment and another.'
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

    public function setDelete($delete = true)
    {
        $this->delete = $delete;

        return $this;
    }

    protected function configure()
    {
        $this->addSetterArgument('file1');
        $this->addSetterArgument('file2');

        $this->addSetterOption('exclude', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL);
        $this->addSetterOption('delete', null, InputOption::VALUE_NONE);

        parent::configure();
    }

    protected function addSteps()
    {
        $paths = new FileTransferPaths($this->getProject(), $this->file1, $this->file2);
        $rsync = new RsyncStep($paths->getLocalPath(), $paths->getRemotePath(), $paths->getDirection());
        $env   = $this->getProject()->getTunneledEnvironment($paths->getRemoteEnvironment());
        $this->setEnvironment($env);

        if (count($this->excludes)) {
            foreach ($this->excludes as $exclude) {
                $rsync->exclude($exclude);
            }
        }

        if ($this->delete) {
            $rsync->delete();
        }

        $this->addStep($rsync);
    }
}
