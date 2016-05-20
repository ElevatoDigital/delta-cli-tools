<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\DisplayLog;
use Symfony\Component\Console\Input\InputOption;

class Log extends Script
{
    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $script;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'log',
            'Display the log from Delta API.'
        );
    }

    protected function configure()
    {
        $this->addSetterOption(
            'environment',
            null,
            InputOption::VALUE_REQUIRED,
            'Only display log entries from a specific environment.'
        );

        $this->addSetterOption(
            'script',
            null,
            InputOption::VALUE_REQUIRED,
            'Only display log entries from a specific script.'
        );

        parent::configure();
    }

    protected function addSteps()
    {
        $displayStep = new DisplayLog($this->getProject());
        $displayStep
            ->setEnvironment($this->environment)
            ->setScript($this->script);
        $this->addStep($displayStep);
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function setScript($script)
    {
        $this->script = $script;

        return $this;
    }
}
