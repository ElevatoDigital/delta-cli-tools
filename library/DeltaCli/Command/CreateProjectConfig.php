<?php

namespace DeltaCli\Command;

use DeltaCli\ConfigTemplate;
use DeltaCli\Exception\ProjectAlreadyConfigured;
use DeltaCli\Exception\SshKeysAlreadyExists;
use DeltaCli\Project;
use DeltaCli\Template\TemplateSet;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;


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

        /* @var $questionHelper QuestionHelper */
        $questionHelper = $this->getHelperSet()->get('question');

        $configTemplate = new ConfigTemplate();

        $this
            ->askNameQuestion($configTemplate, $questionHelper, $input, $output)
            ->askSshKeyQuestion($questionHelper, $input, $output)
            ->askTemplateQuestion($questionHelper, $input, $output);

        $this->project->writeConfig($configTemplate->getContents());
    }

    private function askNameQuestion(
        ConfigTemplate $configTemplate,
        QuestionHelper $questionHelper,
        InputInterface $input,
        OutputInterface $output
    ) {
        $configTemplate->setProjectName(
            $questionHelper->ask(
                $input,
                $output,
                new Question("<question>What is the name of your project?</question>\n")
            )
        );

        return $this;
    }

    private function askSshKeyQuestion(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output)
    {
        $generateSshKeys = $questionHelper->ask(
            $input,
            $output,
            new ConfirmationQuestion(
                "<question>Would you like to generate SSH keys for your project?</question> [y/n]\n"
            )
        );

        if ($generateSshKeys) {
            try {
                $keyGenCommand = new SshKeyGen();
                $keyGenCommand->execute($input, $output);
            } catch (SshKeysAlreadyExists $e) {
                $output->writeln('<comment>SSH keys already generated in ssh-keys folder.</comment>');
            }
        }

        return $this;
    }

    private function askTemplateQuestion(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output)
    {
        $templateSet = new TemplateSet();

        $template = $templateSet->getTemplateByInstallerChoiceKey(
            $questionHelper->ask(
                $input,
                $output,
                new ChoiceQuestion(
                    '<question>Which template would you like to use for your project configuration?</question>',
                    $templateSet->getQuestionChoices()
                )
            )
        );

        $template->install($questionHelper, $input, $output);

        return $this;
    }
}
