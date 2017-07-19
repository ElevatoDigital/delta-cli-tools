<?php

namespace DeltaCli\Extension;

use DeltaCli\Project;
use DeltaCli\Extension\WordPress\Script\Cli as CliScript;

class WordPress implements ExtensionInterface
{
    public function extend(Project $project)
    {
        $project->addScript(new CliScript($project));
    }
}