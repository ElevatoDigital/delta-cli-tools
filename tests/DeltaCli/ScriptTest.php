<?php

namespace DeltaCli;

use DeltaCli\Script\Step\Result;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;


class ScriptTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var BufferedOutput
     */
    private $output;

    /**
     * @var Script
     */
    private $script;

    /**
     * @var Project
     */
    private $project;

    public function setUp()
    {
        $input = new ArgvInput();

        $this->output  = new BufferedOutput();
        $this->project = new Project($input, $this->output);
        $this->script  = new Script($this->project, 'Test', 'Test Script');
    }

    public function testDryRunSkipsStepsThatDoNotSupportIt()
    {
        $noDryRunSupport = false;

        $this->script->addStep(
            function () use (&$noDryRunSupported) {
                $noDryRunSupported = true;
            }
        );

        $this->script->dryRun($this->project->getOutput());

        $this->assertFalse($noDryRunSupport);
        $this->assertContains(Script::NO_DRY_RUN_SUPPORT_EXPLANATION, $this->output->fetch());
    }

    public function testDryRunMethodOfSupportedStepsIsCalledByScriptDryRun()
    {
        $mainCalled   = false;
        $dryRunCalled = false;

        $this->script->addStep(
            $this->project->phpCallableSupportingDryRun(
                function () use (&$mainCalled) {
                    $mainCalled = true;
                },
                function () use (&$dryRunCalled) {
                    $dryRunCalled = true;
                }
            )
        );

        $this->script->dryRun($this->project->getOutput());

        $this->assertFalse($mainCalled);
        $this->assertTrue($dryRunCalled);
    }

    public function testAllExecutionMethodsGetStepsForTheSelectedEnvironment()
    {
        /* @var $script Script|\PHPUnit_Framework_MockObject_MockObject */
        $script = $this->getMock(
            '\DeltaCli\Script',
            ['getStepsForEnvironment'],
            [$this->project, 'run', 'Runs steps.']
        );

        $script->expects($this->once())
            ->method('getStepsForEnvironment')
            ->will($this->returnValue([]));

        $script->runSteps($this->output);

        /* @var $script Script|\PHPUnit_Framework_MockObject_MockObject */
        $script = $this->getMock(
            '\DeltaCli\Script',
            ['getStepsForEnvironment'],
            [$this->project, 'dry-run', 'Does dry run.']
        );

        $script->expects($this->once())
            ->method('getStepsForEnvironment')
            ->will($this->returnValue([]));

        $script->dryRun($this->output);

        /* @var $script Script|\PHPUnit_Framework_MockObject_MockObject */
        $script = $this->getMock(
            '\DeltaCli\Script',
            ['getStepsForEnvironment'],
            [$this->project, 'list-steps', 'Lists steps.']
        );

        $script->expects($this->once())
            ->method('getStepsForEnvironment')
            ->will($this->returnValue([]));

        $script->listSteps($this->output);
    }

    /**
     * @expectedException \DeltaCli\Exception\RequiredVersionNotInstalled
     */
    public function testRunningScriptWithoutNecessaryDeltaCliVersionThrowsException()
    {
        $input  = new ArgvInput();
        $output = new ConsoleOutput();

        $project = new Project($input, $output);
        $project->requiresVersion('1.50.8');

        /* @var $versionMock \PHPUnit_Framework_MockObject_MockObject|ComposerVersion */
        $versionMock = $this->getMock(
            '\DeltaCli\ComposerVersion',
            ['getCurrentVersion'],
            []
        );

        $versionMock->expects($this->any())
            ->method('getCurrentVersion')
            ->will($this->returnValue('1.20'));

        $script = new Script($project, 'Test', 'Test Script');
        $script->setComposerVersionReader($versionMock);
        $script->checkRequiredVersionForProject();
    }

    public function testRunningScriptWithNecessaryDeltaCliVersionReturnsTrue()
    {
        $input  = new ArgvInput();
        $output = new ConsoleOutput();

        $project = new Project($input, $output);
        $project->requiresVersion('1.50.8');

        /* @var $versionMock \PHPUnit_Framework_MockObject_MockObject|ComposerVersion */
        $versionMock = $this->getMock(
            '\DeltaCli\ComposerVersion',
            ['getCurrentVersion'],
            []
        );

        $versionMock->expects($this->any())
            ->method('getCurrentVersion')
            ->will($this->returnValue('1.80'));

        $script = new Script($project, 'Test', 'Test Script');
        $script->setComposerVersionReader($versionMock);
        $this->assertTrue($script->checkRequiredVersionForProject());
    }

    public function testCanSetEnvironmentUsingString()
    {
        $this->project->createEnvironment('test');
        $this->script->setEnvironment('test');
        $this->assertEquals('test', $this->script->getEnvironment()->getName());
    }

    public function testGetStepsForEnvironmentOnlyIncludesStepsForSelectedEnvironment()
    {
        $this->project->createEnvironment('test');
        $this->project->createEnvironment('other-env');
        $this->script->setEnvironment('test');

        $this->script
            ->addStep('global-step', 'fafafa')
            ->addEnvironmentSpecificStep('test', 'environment-step', 'fafafa')
            ->addEnvironmentSpecificStep('other-env', 'excluded-step', 'fafafa');

        $globalStepFound      = false;
        $excludedStepFound    = false;
        $environmentStepFound = false;

        /* @var $step \DeltaCli\Script\Step\StepInterface */
        foreach ($this->script->getStepsForEnvironment() as $step) {
            if ('global-step' === $step->getName()) {
                $globalStepFound = true;
            }

            if ('environment-step' === $step->getName()) {
                $environmentStepFound = true;
            }

            if ('excluded-step' === $step->getName()) {
                $excludedStepFound = true;
            }
        }

        $this->assertTrue($globalStepFound);
        $this->assertFalse($excludedStepFound);
        $this->assertTrue($environmentStepFound);
    }

    /**
     * @expectedException \DeltaCli\Exception\EnvironmentNotAvailableForStep
     */
    public function testExceptionIsThrownIfStepRequiresEnvironmentAndNoneIsSet()
    {
        $this->script->addStep($this->project->ssh('ls'));
        $this->script->getStepsForEnvironment();
    }

    public function testSelectedEnvironmentIsSetOnStepsThatAcceptIt()
    {
        $this->project->createEnvironment('test');
        $this->script->setEnvironment('test');

        $this->project->createScript('other-script', 'Other script for test.');

        /* @var $requiredStep \DeltaCli\Script\Step\Ssh|\PHPUnit_Framework_MockObject_MockObject */
        $requiredStep = $this->getMock(
            '\DeltaCli\Script\Step\Ssh',
            ['setSelectedEnvironment'],
            ['ls']
        );

        $requiredStep->expects($this->once())
            ->method('setSelectedEnvironment');

        /* @var $requiredStep \DeltaCli\Script\Step\Ssh|\PHPUnit_Framework_MockObject_MockObject */
        $optionalStep = $this->getMock(
            '\DeltaCli\Script\Step\Script',
            ['setSelectedEnvironment'],
            [$this->project->getScript('other-script'), $this->project->getInput(), $this->project->getOutput()]
        );

        $optionalStep->expects($this->once())
            ->method('setSelectedEnvironment');

        $this->script
            ->addStep('required', $requiredStep)
            ->addStep('optional', $optionalStep);

        $this->script->getStepsForEnvironment();
    }

    public function testListStepsOnScriptListsAllStepNamesAndClasses()
    {
        $this->script
            ->addStep('step-one', 'ls')
            ->addStep(
                'step-two',
                function () {

                }
            );

        $this->script->listSteps($this->output);

        $output = $this->output->fetch();

        $this->assertContains('step-one', $output);
        $this->assertContains('step-two', $output);
        $this->assertContains('ShellCommand', $output);
        $this->assertContains('PhpCallable', $output);

        // Full class name shouldn't be there.  Just the last segment of it.
        $this->assertNotContains('DeltaCli\\Script\\Step', $output);
    }

    public function testExecutionWillStopAtFailedStepByDefault()
    {
        $firstStepCalled      = false;
        $subsequentStepCalled = false;

        $this->script
            ->addStep(
                'first-step',
                function () use (&$firstStepCalled) {
                    $firstStepCalled = true;
                }
            )
            ->addStep(
                'failing-step',
                function () {
                    throw new \Exception('Oops!');
                }
            )
            ->addStep(
                'next-step',
                function () use (&$subsequentStepCalled) {
                    $subsequentStepCalled = true;
                }
            );

        $this->script->runSteps($this->output);

        $this->assertTrue($firstStepCalled);
        $this->assertFalse($subsequentStepCalled);
    }

    public function testExecutionWillContinueBeyondFailedStepIfSoConfigured()
    {
        $firstStepCalled      = false;
        $subsequentStepCalled = false;

        $this->script
            ->setStopOnFailure(false)
            ->addStep(
                'first-step',
                function () use (&$firstStepCalled) {
                    $firstStepCalled = true;
                }
            )
            ->addStep(
                'failing-step',
                function () {
                    throw new \Exception('Oops!');
                }
            )
            ->addStep(
                'next-step',
                function () use (&$subsequentStepCalled) {
                    $subsequentStepCalled = true;
                }
            );

        $this->script->runSteps($this->output);

        $this->assertTrue($firstStepCalled);
        $this->assertTrue($subsequentStepCalled);
    }

    public function testPreRunHookIsCalledBeforeRunningSteps()
    {
        $step = $this->getMock('\DeltaCli\Script\Step\StepInterface');

        $step->expects($this->once())
            ->method('preRun');

        $step->expects($this->once())
            ->method('run');

        $this->script->addStep($step);

        $this->script->runSteps($this->script->getProject()->getOutput());
    }

    public function testPostRunHookIsCalledAfterRunningSteps()
    {
        $step = $this->getMock('\DeltaCli\Script\Step\StepInterface');

        $step->expects($this->once())
            ->method('run')
            ->willReturn(new Result($step, Result::SUCCESS));

        $step->expects($this->once())
            ->method('postRun');

        $this->script->addStep($step);

        $this->script->runSteps($this->script->getProject()->getOutput());
    }

    public function testSkippedStepsAlsoSkipPostRunHookAndPreRunHook()
    {
        $step = $this->getMock('\DeltaCli\Script\Step\StepInterface');

        $step->expects($this->any())
            ->method('getName')
            ->willReturn('skipped-step');

        $step->expects($this->never())
            ->method('preRun');

        $step->expects($this->never())
            ->method('run');

        $step->expects($this->never())
            ->method('postRun');

        $this->script
            ->addStep($step)
            ->setSkippedSteps(['skipped-step']);

        $this->script->runSteps($this->script->getProject()->getOutput());
    }

    public function testAddStepHookIsCalledWhenAddingAnotherStep()
    {
        $stepOne = $this->getMock('\DeltaCli\Script\Step\StepInterface');
        $stepTwo = $this->getMock('\DeltaCli\Script\Step\StepInterface');

        $stepOne->expects($this->once())
            ->method('addStepToScript')
            ->with($this->script, $stepTwo);

        $this->script
            ->addStep($stepOne)
            ->addStep($stepTwo);

        $this->script->runSteps($this->script->getProject()->getOutput());
    }

    public function testSkippedStepsAreSkippedOnRun()
    {
        $skipped  = $this->getMock('\DeltaCli\Script\Step\StepInterface');
        $executed = $this->getMock('\DeltaCli\Script\Step\StepInterface');

        $skipped->expects($this->never())
            ->method('run');

        $skipped->expects($this->any())
            ->method('getName')
            ->willReturn('skipped-step');

        $executed->expects($this->once())
            ->method('run');

        $this->script
            ->setSkippedSteps(['skipped-step'])
            ->addStep($skipped)
            ->addStep($executed);

        $this->script->runSteps($this->script->getProject()->getOutput());
    }

    public function testGettingNonExistentStepReturnsFalse()
    {
        $this->assertFalse($this->script->getStep('nope'));
    }

    public function testInvalidResultDuringDryRunProducesInvalidResult()
    {
        $output = new BufferedOutput();

        $step = $this->getMock(
            '\DeltaCli\Script\Step\PhpCallableSupportingDryRun',
            ['dryRun'],
            [
                function () {

                },
                function () {

                }
            ]
        );

        $this->script->addStep($step);

        $this->script->dryRun($output);

        $this->assertContains('did not return a valid result', $output->fetch());
    }

    public function testDefaultStepsAreAddedOnceFirstStepIsCreated()
    {
        $default = $this->getMock('\DeltaCli\Script\Step\StepInterface');
        $added   = $this->getMock('\DeltaCli\Script\Step\StepInterface');

        $default->expects($this->any())
            ->method('getName')
            ->willReturn('default');

        $added->expects($this->any())
            ->method('getName')
            ->willReturn('added');

        $this->script
            ->addDefaultStep($default)
            ->addStep($added);

        $this->assertInstanceOf('DeltaCli\Script\Step\StepInterface', $this->script->getStep('default'));
        $this->assertInstanceOf('DeltaCli\Script\Step\StepInterface', $this->script->getStep('added'));
    }
}
