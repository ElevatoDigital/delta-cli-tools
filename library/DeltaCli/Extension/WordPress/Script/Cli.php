<?php

namespace DeltaCli\Extension\WordPress\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;

class Cli extends Script
{
    private $args;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'wp:cli',
            'Run a command with WP CLI.'
        );
    }

    protected function configure()
    {
        parent::configure();

        $this->requireEnvironment();

        $this->addSetterArgument(
            'args',
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'Args to pass along to WP CLI.'
        );
    }

    public function setArgs(array $args)
    {
        $this->args = $args;

        return $this;
    }

    protected function addSteps()
    {
        $this->addStep($this->getProject()->wpCli($this->args));
    }
}