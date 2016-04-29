<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Script as ScriptObject;
use Symfony\Component\Console\Output\BufferedOutput;

class Script extends StepAbstract implements EnvironmentAwareInterface, DryRunInterface
{
    /**
     * @var ScriptObject
     */
    private $script;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var array
     */
    private $skippedSteps = [];

    /**
     * Script constructor.
     * @param ScriptObject $script
     */
    public function __construct(ScriptObject $script)
    {
        $this->script = $script;
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

    public function run()
    {
        $this->script
            ->setEnvironment($this->environment)
            ->setSkippedSteps($this->skippedSteps);

        $output = new BufferedOutput();

        return new Result($this, $this->script->runSteps($output), $output->fetch());
    }

    public function dryRun()
    {
        $this->script
            ->setEnvironment($this->environment)
            ->setSkippedSteps($this->skippedSteps);

        $output = new BufferedOutput();

        $this->script->dryRun($output);

        return new Result($this, Result::SUCCESS, $output->fetch());
    }
}
