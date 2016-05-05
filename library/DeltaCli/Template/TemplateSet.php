<?php

namespace DeltaCli\Template;

class TemplateSet
{
    /**
     * @var Custom
     */
    private $customTemplate;

    private $templates = [];

    public function __construct()
    {
        $this->customTemplate = new Custom();

        $this->templates[] = new WordPress();
        $this->templates[] = new Custom();
    }

    public function getAll()
    {
        return $this->templates;
    }

    public function getQuestionChoices()
    {
        $choices = [];

        /* @var $template TemplateInterface */
        foreach ($this->templates as $template) {
            $choices[$template->getInstallerChoiceKey()] = $template->getName();
        }

        return $choices;
    }

    /**
     * @param string $installerChoiceKey
     * @return TemplateInterface
     */
    public function getTemplateByInstallerChoiceKey($installerChoiceKey)
    {
        /* @var $template TemplateInterface */
        foreach ($this->templates as $template) {
            if ($template->getInstallerChoiceKey() === $installerChoiceKey) {
                return $template;
            }
        }

        return $this->customTemplate;
    }
}
