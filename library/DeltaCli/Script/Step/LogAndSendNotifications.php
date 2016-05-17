<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Project;
use DeltaCli\Script;

class LogAndSendNotifications extends StepAbstract
{
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return 'log-and-send-notifications';
        }
    }

    public function preRun(Script $script)
    {
        // @todo If no API key is available, kick off the sign-up process.
    }

    public function run()
    {
        // @todo Send the notifications via the DeltaApi library
    }
}
