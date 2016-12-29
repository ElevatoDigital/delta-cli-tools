<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Exception\CommandNotFound;
use DeltaCli\Exception\SshConnectionFailure;
use DeltaCli\Exec;
use DeltaCli\Host;
use DeltaCli\Script;

abstract class StepAbstract implements StepInterface
{
    /**
     * @const
     */
    const COMMAND_NOT_FOUND = 127;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $environments = [];

    /**
     * @var callable
     */
    protected $commandRunner;

    abstract public function run();

    /**
     * @param $environment
     * @return $this
     */
    public function setEnvironments(array $environments)
    {
        $this->environments = $environments;

        return $this;
    }

    public function checkIfExecutableExists($executable, $testCommand)
    {
        $command = sprintf('%s 2>&1', $testCommand);

        $this->exec($command, $output, $exitStatus);

        if (self::COMMAND_NOT_FOUND === $exitStatus) {
            $exception = new CommandNotFound();
            $exception->setCommand($executable);
            throw $exception;
        }
    }

    public function setCommandRunner(callable $commandRunner)
    {
        $this->commandRunner = $commandRunner;

        return $this;
    }

    public function getCommandRunner()
    {
        if (!$this->commandRunner) {
            $this->commandRunner = Exec::getCommandRunner();
        }

        return $this->commandRunner;
    }

    public function exec($command, &$output, &$exitStatus)
    {
        /* @var $commandRunner callable */
        $commandRunner = $this->getCommandRunner();
        $commandRunner($command, $output, $exitStatus);
    }

    public function execSsh(Host $host, $command, &$output, &$exitStatus)
    {
        $this->exec($command, $output, $exitStatus);

        if ($exitStatus && $this->outputContains($output, ['permission denied', 'publickey'])) {
            $exception = new SshConnectionFailure();
            $exception
                ->setHost($host)
                ->setFailingCommand($command)
                ->setOutput($output);
            throw $exception;
        }
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function preRun(Script $script)
    {

    }

    public function addStepToScript(Script $script, StepInterface $step)
    {

    }

    public function postRun(Script $script)
    {

    }

    public function appliesToEnvironment(Environment $selectedEnvironment)
    {
        if (!count($this->environments)) {
            return true;
        }

        /* @var $environment Environment */
        foreach ($this->environments as $environment) {
            $environmentName = (is_string($environment) ? $environment : $environment->getName());

            if ($environmentName === $selectedEnvironment->getName()) {
                return true;
            }
        }

        return false;
    }

    private function outputContains(array $output, array $stringsToMatch)
    {
        foreach ($output as $line) {
            $lineMatchesAll = true;

            foreach ($stringsToMatch as $searchString) {
                if (false === stripos($line, $searchString)) {
                    $lineMatchesAll = false;
                    break;
                }
            }

            if ($lineMatchesAll) {
                return true;
            }
        }

        return false;
    }
}
