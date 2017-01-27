<?php

namespace DeltaCli\Template;

use DeltaCli\Project;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface TemplateInterface
{
    public function getInstallerChoiceKey();

    public function getName();

    public function install(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output);

    public function apply(Project $project);

    public function postLoadConfig(Project $project);
}
