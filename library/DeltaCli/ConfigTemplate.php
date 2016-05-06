<?php

namespace DeltaCli;

class ConfigTemplate
{
    private $projectName;

    private $projectTemplateContent;

    public function setProjectName($projectName)
    {
        $this->projectName = $projectName;

        return $this;
    }

    public function setProjectTemplateContent($projectTemplateContent)
    {
        $this->projectTemplateContent = $projectTemplateContent;

        return $this;
    }

    public function getContents()
    {
        return sprintf(
            file_get_contents(__DIR__ . '/_files/delta-cli.php.template'),
            $this->projectName,
            $this->projectTemplateContent
        );
    }
}
