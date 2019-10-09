<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class SetSyncedDirPath extends Script
{
    private $path;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:set-synced-dir-path',
            'Set the path to your synced /delta directory if it is not in root.'
        );
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    protected function configure()
    {
        $this->addSetterArgument(
            'path',
            InputArgument::REQUIRED,
            'The path to your synced /delta direcotry. This is the directory you will clone projects into, usually at root level.'
        );

        parent::configure();
    }

    protected function addSteps()
    {
        $this
            ->addStep(
                'set-synced-dir-path',
                function () {
                    $this->getProject()->getGlobalCache()->store('synced-dir-path', realpath($this->path));
                }
            );
    }
}
