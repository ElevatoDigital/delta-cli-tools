<?php

namespace DeltaCli\Command;

use DeltaCli\Command;
use DeltaCli\Exception\CouldNotFindGenerateTemplate;
use DeltaCli\Exception\InvalidGenerateTemplate as InvalidGenerateTemplateException;
use DeltaCli\Generate\AbstractTemplateSet;
use DeltaCli\Generate\DeltaZend\TemplateSet as DeltaZendTemplateSet;
use DeltaCli\Project;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Generate extends Command
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var AbstractTemplateSet[]
     */
    private $templateSets = [];

    public function __construct(Project $project)
    {
        parent::__construct(null);

        $this->project = $project;
    }

    protected function configure()
    {
        $this
            ->setName('generate')
            ->setDescription('Generate commonly needed classes and files.');

        $this->addArgument(
            'template',
            InputArgument::REQUIRED,
            'The template to use when generating code.'
        );

        $this->setHelp('<fg=green>Hey, it works!</>' . PHP_EOL . '<fg=cyan>Yup</>');
    }

    public function getHelp()
    {
        $out = [];

        foreach ($this->getTemplateSets() as $templateSet) {
            $out = array_merge($out, $templateSet->getHelp());
        }

        return implode(PHP_EOL, $out);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (false === strpos($input->getArgument('template'), ':')) {
            throw new InvalidGenerateTemplateException(
                sprintf(
                    "'%s' is not a valid template name.",
                    $input->getArgument('template')
                )
            );
        }

        list($namespace, $templateName) = explode(':', $input->getArgument('template'));

        $selectedTemplate = null;

        foreach ($this->getTemplateSets() as $templateSet) {
            if ($templateSet->getNamespace() !== $namespace) {
                continue;
            }

            foreach ($templateSet->getTemplates() as $template) {
                if ($template->getName() === $templateName) {
                    $selectedTemplate = $template;
                    break;
                }
            }
        }

        if (!$selectedTemplate) {
            throw new CouldNotFindGenerateTemplate(
                sprintf(
                    "Could not find a generate template matching '%s'.",
                    $input->getArgument('template')
                )
            );
        }

        $selectedTemplate->displayInitialHelpAndDocumentation($output);
        $selectedTemplate->generateFiles($this->getHelper('question'), $input, $output);
    }

    protected function getTemplateSets()
    {
        if (!count($this->templateSets)) {
            $this->templateSets[] = new DeltaZendTemplateSet();
        }

        return $this->templateSets;
    }
}
