<?php

namespace DeltaCli\Template;

use DeltaCli\Project;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Custom implements TemplateInterface
{
    public function getInstallerChoiceKey()
    {
        return 'c';
    }

    public function getName()
    {
        return 'Custom Project';
    }

    public function install(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output)
    {

    }

    public function apply(Project $project)
    {

    }

    public function postLoadConfig(Project $project)
    {

    }
}
