<?php

namespace DeltaCli;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Exception\EnvironmentNotAvailableForStep;
use DeltaCli\Exception\RequiredVersionNotInstalled;
use DeltaCli\Exception\SetterNotPresentForScriptOption;
use DeltaCli\Script\Step\DryRunInterface;
use DeltaCli\Script\Step\EnvironmentAwareInterface;
use DeltaCli\Script\Step\EnvironmentOptionalInterface;
use DeltaCli\Script\Step\Result;
use DeltaCli\Script\Step\StepFactory;
use DeltaCli\Script\Step\StepInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Script extends Command
{
    const NO_DRY_RUN_SUPPORT_EXPLANATION = 'because it does not support dry runs';

    /**
     * @var Project
     */
    private $project;

    /**
     * @var ApiResults
     */
    private $apiResults;

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
     * @var bool
     */
    private $addStepsRun = false;

    /**
     * @var StepFactory
     */
    private $stepFactory;

    /**
     * @var bool
     */
    private $stopOnFailure = true;

    /**
     * @var bool
     */
    private $showStatusOutput = true;

    /**
     * @var array
     */
    private $setterArguments = [];

    /**
     * @var array
     */
    private $setterOptions = [];

    /**
     * @var callable
     */
    private $placeholderCallback;

    /**
     * @var ComposerVersion
     */
    private $composerVersionReader;

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

        $this->apiResults            = new ApiResults($this);
        $this->project               = $project;
        $this->composerVersionReader = new ComposerVersion();

        if (null === $stepFactory) {
            $stepFactory = new StepFactory($this->project->getInput(), $this->project->getOutput());
        }

        $this->stepFactory = $stepFactory;

        $this->init();
    }

    protected function init()
    {

    }

    protected function addSteps()
    {

    }

    public function setComposerVersionReader(ComposerVersion $composerVersionReader)
    {
        $this->composerVersionReader = $composerVersionReader;

        return $this;
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
                'colors',
                null,
                InputOption::VALUE_NONE,
                'Force color output.  Useful with `less -R`.'
            )
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
            )
            ->addOption(
                'hide-status-output',
                null,
                InputOption::VALUE_NONE,
                'Only display output from the steps themselves rather than Delta CLI status information.'
            );
    }

    protected function addSetterArgument($name, $mode = null, $description = '', $default = null)
    {
        $setter = $this->inflectSetterFromOptionName($name);

        if (!method_exists($this, $setter)) {
            $scriptClass = get_class($this);
            throw new SetterNotPresentForScriptOption("{$name} has no associated setter on {$scriptClass}.");
        }

        $this->addArgument($name, $mode, $description, $default);

        $this->setterArguments[$name] = $setter;

        return $this;
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

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->project->loadConfigFile();

        if ($input->hasArgument('environment')) {
            $this->setEnvironment($this->project->getSelectedEnvironment());
        }

        foreach ($this->setterArguments as $argument => $setterMethod) {
            $this->$setterMethod($input->getArgument($argument));
        }

        foreach ($this->setterOptions as $option => $setterMethod) {
            if ($input->getOption($option)) {
                $this->$setterMethod($input->getOption($option));
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkRequiredVersionForProject();

        if ($input->hasOption('colors') && $input->getOption('colors')) {
            $output->setDecorated(true);
        }

        if (!count($this->steps) && $this->placeholderCallback) {
            $placeholderCallback = $this->placeholderCallback;
            $placeholderCallback($output);
            exit;
        }

        if ($input->hasOption('hide-status-output') && $input->getOption('hide-status-output')) {
            $this->showStatusOutput = false;
        }

        $this->skippedSteps = $input->getOption('skip-step');

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

    /**
     * @param mixed $environment
     * @return $this
     * @throws Exception\EnvironmentNotFound
     */
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

    public function getApiResults()
    {
        return $this->apiResults;
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
        $this->addDefaultSteps();

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
        $this->addDefaultSteps();

        $newStep = $this->stepFactory->factory(func_get_args());

        /* @var $step StepInterface */
        foreach ($this->steps as $step) {
            $step->addStepToScript($this, $newStep);
        }

        $this->steps[] = $newStep;

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

        $steps = $this->getStepsForEnvironment();

        /* @var $step StepInterface */
        foreach ($steps as $step) {
            if (!$this->stepShouldBeSkipped($step)) {
                $step->preRun($this);
            }
        }

        /* @var $step StepInterface */
        foreach ($steps as $step) {
            if ($this->stepShouldBeSkipped($step)) {
                $result = new Result($step, Result::SKIPPED);
                $result->setExplanation("at the user's request");
            } else {
                $result = $step->run();

                if (!$result instanceof Result) {
                    $result = new Result($step, Result::INVALID);
                }
            }

            $result->render($output, $this->showStatusOutput);

            $this->apiResults->addStepResult($result);

            if ($this->stopOnFailure && $result->isFailure()) {
                $scriptResult = Result::FAILURE;

                $output->writeln('');

                $banner = new Banner($output);
                $banner->setBackground('red');
                $banner->render('Halting script execution due to failure of previous step.');
                break;
            }
        }

        $this->apiResults->setScriptResult($scriptResult);

        /* @var $step StepInterface */
        foreach ($steps as $step) {
            if (!$this->stepShouldBeSkipped($step)) {
                $step->postRun($this);
            }
        }

        return $scriptResult;
    }

    public function dryRun(OutputInterface $output)
    {
        /* @var $step StepInterface|DryRunInterface */
        foreach ($this->getStepsForEnvironment() as $step) {
            if ($this->stepShouldBeSkipped($step)) {
                $result = new Result($step, Result::SKIPPED);
                $result->setExplanation("at the user's request");
            } elseif (!$step instanceof DryRunInterface) {
                $result = new Result($step, Result::SKIPPED);
                $result->setExplanation(self::NO_DRY_RUN_SUPPORT_EXPLANATION);
            } else {
                $result = $step->dryRun();

                if (!$result instanceof Result) {
                    $result = new Result($step, Result::INVALID);
                }
            }

            $result->render($output, $this->showStatusOutput);
        }
    }

    public function listSteps(OutputInterface $output)
    {
        /* @var $step StepInterface */
        foreach ($this->getStepsForEnvironment() as $step) {
            $classSuffix = get_class($step);

            if (false !== strpos($classSuffix, '\\')) {
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

    public function getStepsForEnvironment()
    {
        if (!$this->addStepsRun) {
            $this->addSteps();
            $this->addStepsRun = true;
        }

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

    public function checkRequiredVersionForProject()
    {
        if ($this->project->getMinimumVersionRequired()) {
            $requiredVersion = $this->project->getMinimumVersionRequired();
            $currentVersion  = $this->composerVersionReader->getCurrentVersion();

            if ('git' !== $currentVersion && version_compare($currentVersion, $requiredVersion, '<')) {
                $exception = new RequiredVersionNotInstalled();
                $exception->setRequiredVersion($requiredVersion);
                throw $exception;
            }
        }

        return true;
    }

    private function addDefaultSteps()
    {
        if (0 === count($this->steps)) {
            foreach ($this->defaultSteps as $step) {
                $this->steps[] = $step;
            }
        }
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
