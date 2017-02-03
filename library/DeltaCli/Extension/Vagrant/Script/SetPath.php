<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class SetPath extends Script
{
    private $path;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:set-path',
            'Set the path to your Vagrant environment if it cannot be detected.'
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
            'The path to your Vagrant.  Should be the folder containing your Vagrantfile.'
        );

        parent::configure();
    }

    protected function addSteps()
    {
        $this
            ->addStep(
                'set-path',
                function () {
                    $this->getProject()->getGlobalCache()->store('vagrant-path', realpath($this->path));
                }
            );
    }
}
