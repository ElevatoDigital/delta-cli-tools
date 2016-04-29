<?php

namespace DeltaCli\Command;

use DeltaCli\ConfigTemplate;
use DeltaCli\Exception\ProjectAlreadyConfigured;
use DeltaCli\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateProjectConfig extends Command
{
    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        parent::__construct(null);

        $this->project = $project;
    }

    protected function configure()
    {
        $this
            ->setName('create-project-config')
            ->setDescription('Create a new delta-cli.php file for your project.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->project->configFileExists()) {
            throw new ProjectAlreadyConfigured('A delta-cli.php already exists for your project.');
        }

        /* @var $questionHelper \Symfony\Component\Console\Helper\QuestionHelper */
        $questionHelper = $this->getHelperSet()->get('question');

        $configTemplate = new ConfigTemplate();

        $configTemplate
            ->setProjectName(
                $questionHelper->ask(
                    $input,
                    $output,
                    new Question("What is the name of your project?\n")
                )
            );

        $this->project->writeConfig($configTemplate->getContents());
    }
}
