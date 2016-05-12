<?php

namespace DeltaCli\Script\Step;

class ShellCommandSupportingDryRun extends ShellCommand implements DryRunInterface
{
    private $dryRunCommand;

    public function __construct($command, $dryRunCommand)
    {
        parent::__construct($command);

        $this->dryRunCommand = $dryRunCommand;
    }

    public function dryRun()
    {
        return $this->runCommand($this->dryRunCommand);
    }
}
