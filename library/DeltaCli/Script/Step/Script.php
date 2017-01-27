<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Script as ScriptObject;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Script extends StepAbstract implements EnvironmentOptionalInterface, DryRunInterface
{
    /**
     * @var ScriptObject
     */
    private $script;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var array
     */
    private $skippedSteps = [];

    /**
     * @var bool
     */
    private $useConsoleOutput = false;

    /**
     * Script constructor.
     * @param ScriptObject $script
     */
    public function __construct(ScriptObject $script, InputInterface $input, OutputInterface $output)
    {
        $this->script = $script;
        $this->input  = $input;
        $this->output = $output;
    }

    public function getName()
    {
        return $this->script->getName();
    }

    public function setSelectedEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function setUseConsoleOutput($useConsoleOutput)
    {
        $this->useConsoleOutput = $useConsoleOutput;

        return $this;
    }

    public function run()
    {
        $this->script->setSkippedSteps($this->skippedSteps);

        if ($this->environment) {
            $this->script->setEnvironment($this->environment);
        }

        $output = $this->getOutput();
        $status = $this->script->runSteps($output, $this->getProgressBar());

        return new Result(
            $this,
            $status,
            ($this->useConsoleOutput ? [] : $output->fetch())
        );
    }

    public function dryRun()
    {
        $this->script
            ->setEnvironment($this->environment)
            ->setSkippedSteps($this->skippedSteps);

        $output = $this->getOutput();

        $this->script->dryRun($this->getOutput());

        return new Result($this, Result::SUCCESS, $output->fetch());
    }

    private function getOutput()
    {
        if ($this->useConsoleOutput) {
            return $this->output;
        } else {
            $output = new BufferedOutput();
            $output->setVerbosity($this->output->getVerbosity());
            return $output;
        }
    }

    private function getProgressBar()
    {
        if ($this->useConsoleOutput) {
            return null;
        }

        $progressBar = new ProgressBar($this->output, $this->script->getStepCount());

        if ($this->script->getEnvironment()) {
            $progressBar->setFormatDefinition(
                'custom',
                "<comment>☰ Running script {$this->getName()} on {$this->script->getEnvironment()->getName()}..."
                . '</comment>' . PHP_EOL
                . "%current%/%max% [%bar%] %message%" . PHP_EOL
            );
        } else {
            $progressBar->setFormatDefinition(
                'custom',
                "<comment>☰ Running script {$this->getName()}...</comment>" . PHP_EOL
                . "%current%/%max% [%bar%] %message%"
            );
        }

        $progressBar->setFormat('custom');

        return $progressBar;
    }
}
