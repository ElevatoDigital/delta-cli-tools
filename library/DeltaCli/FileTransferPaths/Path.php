<?php

namespace DeltaCli\FileTransferPaths;

use DeltaCli\Exception\EnvironmentNotFound;
use DeltaCli\Project;

class Path
{
    private $path;

    private $isRemote;

    private $environment;

    public function __construct(Project $project, $path)
    {
        $colonPos = strpos($path, ':');

        if (false === $colonPos) {
            $this->path     = $path;
            $this->isRemote = false;
        } else {
            $environment = substr($path, 0, $colonPos);

            if (!$project->hasEnvironment($environment)) {
                throw new EnvironmentNotFound();
            }

            $this->path        = substr($path, $colonPos + 1);
            $this->environment = $environment;
            $this->isRemote    = true;
        }
    }

    public function isLocal()
    {
        return !($this->isRemote());
    }

    public function isRemote()
    {
        return $this->isRemote;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }
}
