<?php

namespace DeltaCli;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Exception\EnvironmentNotAvailableForStep;
use DeltaCli\Exception\SetterNotPresentForScriptOption;
use DeltaCli\Script\Step\DryRunInterface;
use DeltaCli\Script\Step\EnvironmentAwareInterface;
use DeltaCli\Script\Step\EnvironmentOptionalInterface;
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
    private $defaultSteps = [];

    /**
     * @var array
     */
    private $skippedSteps = [];

    /**
     * @var StepFactory
     */
    private $stepFactory;

    /**
     * @var bool
     */
    private $stopOnFailure = true;

    /**
     * @var array
     */
    private $setterOptions = [];

    /**
     * @var callable
     */
    private $placeholderCallback;

    /**
     * Script constructor.
     * @param Project $project
     * @param $name
     * @param $description
     * @param StepFactory|null $stepFactory
     */
    public function __construct(Project $project, $name, $description, StepFactory $stepFactory = null)
    {
        parent::__construct($name);

        $this->setDescription($description);

        $this->project = $project;
        $this->stepFactory = ($stepFactory ?: new StepFactory());

        $this->init();
    }

    protected function init()
    {

    }

    public function requireEnvironment()
    {
        $this->addArgument('environment', InputArgument::REQUIRED);
        return $this;
    }

    protected function configure()
    {
        $this
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

    protected function addSetterOption($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        $setter = $this->inflectSetterFromOptionName($name);

        if (!method_exists($this, $setter)) {
            $scriptClass = get_class($this);
            throw new SetterNotPresentForScriptOption("{$name} has no associated setter on {$scriptClass}.");
        }

        $this->addOption($name, $shortcut, $mode, $description, $default);

        $this->setterOptions[$name] = $setter;

        return $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->project->loadConfigFile();

        if (!count($this->steps) && $this->placeholderCallback) {
            $placeholderCallback = $this->placeholderCallback;
            $placeholderCallback($output);
            exit;
        }

        if ($input->hasArgument('environment')) {
            $this->setEnvironment($input->getArgument('environment'));
        }

        $this->skippedSteps = $input->getOption('skip-step');

        foreach ($this->setterOptions as $option => $setterMethod) {
            $this->$setterMethod($input->getOption($option));
        }

        if ($input->getOption('dry-run')) {
            $this->dryRun($output);
        } else if ($input->getOption('list-steps')) {
            $this->listSteps($output);
        } else {
            $this->runSteps($output);
        }
    }

    public function getProject()
    {
        return $this->project;
    }

    public function setEnvironment($environment)
    {
        if (is_string($environment)) {
            $environment = $this->project->getEnvironment($environment);
        }

        $this->environment = $environment;

        return $this;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function setPlaceholderCallback(callable $placeholderCallback)
    {
        $this->placeholderCallback = $placeholderCallback;

        return $this;
    }

    public function setSkippedSteps(array $skippedSteps)
    {
        $this->skippedSteps = $skippedSteps;

        return $this;
    }

    public function getStep($name)
    {
        /* @var $step StepInterface */
        foreach ($this->steps as $step) {
            if ($step->getName() === $name) {
                return $step;
            }
        }

        return false;
    }

    public function addStep()
    {
        if (0 === count($this->steps)) {
            foreach ($this->defaultSteps as $step) {
                $this->steps[] = $step;
            }
        }

        $this->steps[] = $this->stepFactory->factory(func_get_args());
        return $this;
    }

    public function addDefaultStep()
    {
        $this->defaultSteps[] = $this->stepFactory->factory(func_get_args());
        return $this;
    }

    public function addEnvironmentSpecificStep($environmentNames, $stepInput)
    {
        $step = $this->stepFactory->factory(array_slice(func_get_args(), 1));
        $step->setEnvironments($this->getEnvironmentsFromNames($environmentNames));
        $this->addStep($step);
        return $this;
    }

    public function runSteps(OutputInterface $output)
    {
        $scriptResult = Result::SUCCESS;

        /* @var $step StepInterface */
        foreach ($this->getStepsForEnvironment() as $step) {
            if ($this->stepShouldBeSkipped($step)) {
                $result = new Result($step, Result::SKIPPED);
                $result->setExplanation("at the user's request");
            } else {
                $result = $step->run();

                if (!$result instanceof Result) {
                    $result = new Result($step, Result::INVALID);
                }
            }

            $result->render($output);

            if ($this->stopOnFailure && $result->isFailure()) {
                $scriptResult = Result::FAILURE;

                $output->writeln('');

                $banner = new Banner($output);
                $banner->setBackground('red');
                $banner->render('Halting script execution due to failure of previous step.');
                break;
            }
        }

        return $scriptResult;
    }

    public function dryRun(OutputInterface $output)
    {
        /* @var $step StepInterface|DryRunInterface */
        foreach ($this->getStepsForEnvironment() as $step) {
            if (!$step instanceof DryRunInterface) {
                $result = new Result($step, Result::SKIPPED);
                $result->setExplanation('because it does not support dry runs');
            } else {
                $result = $step->dryRun();

                if (!$result instanceof Result) {
                    $result = new Result($step, Result::INVALID);
                }
            }

            $result->render($output);
        }
    }

    public function listSteps(OutputInterface $output)
    {
        /* @var $step StepInterface */
        foreach ($this->getStepsForEnvironment() as $step) {
            $classSuffix = get_class($step);

            if (false !== strpos('\\', $classSuffix)) {
                $classSuffix = substr($classSuffix, strrpos($classSuffix, '\\') + 1);
            }

            $output->writeln(
                sprintf(
                    '%s (%s)',
                    $step->getName(),
                    $classSuffix
                )
            );
        }
    }

    public function setStopOnFailure($stopOnFailure)
    {
        $this->stopOnFailure = $stopOnFailure;

        return $this;
    }

    private function getEnvironmentsFromNames($environmentNames)
    {
        if (!is_array($environmentNames)) {
            $environmentNames = [$environmentNames];
        }

        $environments = [];

        foreach ($environmentNames as $environmentName) {
            $environments[] = $this->project->getEnvironment($environmentName);
        }

        return $environments;
    }

    private function stepShouldBeSkipped(StepInterface $step)
    {
        return in_array($step->getName(), $this->skippedSteps);
    }

    private function getStepsForEnvironment()
    {
        $stepsForEnvironment = [];

        /* @var $step StepInterface|EnvironmentAwareInterface */
        foreach ($this->steps as $step) {
            if ($this->environment && !$step->appliesToEnvironment($this->environment)) {
                continue;
            }

            if ($step instanceof EnvironmentAwareInterface) {
                if (!$this->environment) {
                    $stepClass   = get_class($step);
                    $scriptClass = get_class($this);

                    throw new EnvironmentNotAvailableForStep(
                        "{$stepClass} needs an environment but {$this->getName()} ({$scriptClass}) "
                        . 'does not have one set.'
                    );
                }

                $step->setSelectedEnvironment($this->environment);
            }

            if ($this->environment && $step instanceof EnvironmentOptionalInterface) {
                $step->setSelectedEnvironment($this->environment);
            }

            $stepsForEnvironment[] = $step;
        }

        return $stepsForEnvironment;
    }

    private function inflectSetterFromOptionName($name)
    {
        if (preg_match_all('/\/(.?)/', $name, $got)) {
            foreach ($got[1] as $k => $v) {
                $got[1][$k] = '::'.strtoupper($v);
            }
            $name = str_replace($got[0], $got[1], $name);
        }

        $camelized = str_replace(
            ' ',
            '',
            ucwords(
                preg_replace('/[^A-Z^a-z^0-9^:]+/', ' ', $name)
            )
        );

        return 'set' . $camelized;
    }
}
