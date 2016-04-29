<?php

namespace DeltaCli;

class ConfigTemplate
{
    private $projectName;
    
    public function setProjectName($projectName)
    {
        $this->projectName = $projectName;

        return $this;
    }

    public function getContents()
    {
        return sprintf(
            file_get_contents(__DIR__ . '/_files/delta-cli.php'),
            $this->projectName
        );
    }
}
