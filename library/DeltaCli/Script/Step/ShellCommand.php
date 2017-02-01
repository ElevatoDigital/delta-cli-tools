<?php

namespace DeltaCli\Script\Step;

use Cocur\Slugify\Slugify;

class ShellCommand extends StepAbstract
{
    /**
     * @var string
     */
    protected $command;

    /**
     * @var bool
     */
    private $captureStdErr = true;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function setCaptureStdErr($captureStdErr)
    {
        $this->captureStdErr = $captureStdErr;

        return $this;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return (new Slugify)->slugify($this->command);
        }
    }

    public function run()
    {
        return $this->runCommand($this->command);
    }

    protected function runCommand($command)
    {
        if ($this->captureStdErr) {
            $command .= ' 2>&1';
        }

        $this->exec($command, $output, $exitStatus);

        if (!$exitStatus) {
            return new Result($this, Result::SUCCESS, $output);
        } else {
            $result = new Result($this, Result::FAILURE, $output);
            $result->setExplanation(" with an exit status of {$exitStatus}");
            return $result;
        }
    }
}
