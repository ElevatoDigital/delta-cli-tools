<?php

namespace DeltaCli;

use DeltaCli\Script\Step\Result;
use DeltaCli\Script\Step\StepFactory;
use DeltaCli\Script\Step\StepInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Script extends Command
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var array
     */
    private $steps = [];

    /**
     * @var array
     */
    private $skippedSteps = [];

    /**
     * @var StepFactory
     */
    private $stepFactory;

    public function __construct(Project $project, $name, $description, StepFactory $stepFactory = null)
    {
        parent::__construct($name);

        $this->setDescription($description);

        $this->project     = $project;
        $this->stepFactory = ($stepFactory ?: new StepFactory());
    }

    protected function configure()
    {
        $this
            ->addArgument('environment', InputArgument::REQUIRED)
            ->addOption(
                'skip-step',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Specify one or more step names you would like to skip.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Perform a dry run.  Steps that do not support dry runs will be skipped.'
            )
            ->addOption(
                'list-steps',
                null,
                InputOption::VALUE_NONE,
                'List the steps involved in this script.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->environment  = $this->project->getEnvironment($input->getArgument('environment'));
        $this->skippedSteps = $input->getOption('skip-step');

        if ($input->getOption('dry-run')) {
            $this->dryRun();
        } else if ($input->getOption('list-steps')) {
            $this->listSteps();
        } else {
            $this->runSteps($output);
        }
    }

    public function addStep()
    {
        $this->steps[] = $this->stepFactory->factory(func_get_args());
        return $this;
    }

    public function addEnvironmentSpecificStep($environmentNames, $stepInput)
    {
        $args = func_get_args();
        array_shift($args);

        if (!is_array($environmentNames)) {
            $environmentNames = [$environmentNames];
        }

        $environments = [];

        foreach ($environmentNames as $environmentName) {
            $environments[] = $this->project->getEnvironment($environmentName);
        }

        $step = $this->stepFactory->factory($args);
        $step->setEnvironments($environments);
        $this->addStep($step);
        return $this;
    }

    public function runSteps(OutputInterface $output)
    {
        /* @var $step StepInterface */
        foreach ($this->getStepsForEnvironment() as $step) {
            if ($this->stepShouldBeSkipped($step)) {
                $result = new Result($step, Result::SKIPPED);
            } else {
                $result = $step->run();

                if (!$result instanceof Result) {
                    $result = new Result($step, Result::INVALID);
                }
            }

            $result->render($output);
        }
    }

    public function dryRun()
    {

    }

    public function listSteps()
    {

    }

    private function stepShouldBeSkipped(StepInterface $step)
    {
        return in_array($step->getName(), $this->skippedSteps);
    }

    private function getStepsForEnvironment()
    {
        $stepsForEnvironment = [];

        /* @var $step StepInterface */
        foreach ($this->steps as $step) {
            if ($step->appliesToEnvironment($this->environment)) {
                $stepsForEnvironment[] = $step;
            }
        }

        return $stepsForEnvironment;
    }
}
