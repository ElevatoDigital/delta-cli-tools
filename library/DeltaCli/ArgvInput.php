<?php

namespace DeltaCli;

use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput as SymfonyArgvInput;

class ArgvInput extends SymfonyArgvInput
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var Command
     */
    private $command;

    public function setProject(Project $project)
    {
        $this->project = $project;

        return $this;
    }

    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

    public function validate()
    {
        try {
            parent::validate();
        } catch (RuntimeException $e) {
            $help = new HelpCommand();
            $help->setCommand($this->command);
            $help->run($this, $this->project->getOutput());
            exit;
        }
    }
}
