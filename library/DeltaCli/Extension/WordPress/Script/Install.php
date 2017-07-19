<?php

namespace DeltaCli\Extension\WordPress\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputOption;

class Install extends Script
{
    private $force = false;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'wp:install',
            'Install WordPress with stock Delta themes and plugins.'
        );
    }

    protected function configure()
    {
        $this->addSetterOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Force install even if remote folder is not empty.'
        );
    }

    public function setForce($force)
    {
        $this->force = $force;

        return $this;
    }

    protected function addSteps()
    {
        // @todo Detect non-empty httpdocs and stop unless --force option is set
        // @todo Download core
        // @todo Create and edit wp-config
        // @todo Download public plugins
        // @todo Download themes and plugins from Delta repos
    }
}