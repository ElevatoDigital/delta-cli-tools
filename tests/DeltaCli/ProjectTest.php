<?php

namespace DeltaCli;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ProjectTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Project
     */
    private $project;

    public function setUp()
    {
        $this->project = new Project(new Application(), new ArgvInput(), new ConsoleOutput());
    }

    public function testCanProperlyInstantiatePhpCallableSupportingDryRunStep()
    {
        $mainCalled   = false;
        $dryRunCalled = false;

        $main = function () use (&$mainCalled) {
            $mainCalled = true;
        };

        $dryRun = function () use (&$dryRunCalled) {
            $dryRunCalled = true;
        };

        $step = $this->project->phpCallableSupportingDryRun($main, $dryRun);

        $step->run();

        $this->assertTrue($mainCalled);
        $this->assertFalse($dryRunCalled);

        $mainCalled   = false;
        $dryRunCalled = false;

        $step = $this->project->phpCallableSupportingDryRun($main, $dryRun);

        $step->dryRun();

        $this->assertFalse($mainCalled);
        $this->assertTrue($dryRunCalled);
    }

    public function testCanProperlyInstantiateShellCommandSupportingDryRunStep()
    {
        $mainCommand   = 'main-shell-command-not-found-' . mt_rand(10000, 100000000);
        $dryRunCommand = 'dry-run-shell-command-not-found-' . mt_rand(10000, 10000000);

        $step = $this->project->shellCommandSupportingDryRun($mainCommand, $dryRunCommand);
        $step->setName('shell-command-test');

        $result = $step->run();
        $output = new BufferedOutput();
        $result->render($output);
        $resultOutput = $output->fetch();

        $this->assertContains($mainCommand, $resultOutput);
        $this->assertNotContains($dryRunCommand, $resultOutput);

        $result = $step->dryRun();
        $output = new BufferedOutput();
        $result->render($output);
        $resultOutput = $output->fetch();

        $this->assertNotContains($mainCommand, $resultOutput);
        $this->assertContains($dryRunCommand, $resultOutput);
    }

    public function testCanSpecifyRequiredVersion()
    {
        $this->project->requiresVersion('1.20.0');

        $this->assertEquals('1.20.0', $this->project->getMinimumVersionRequired());
    }

    public function testCanCreateAScriptAndThenRetrieveItFromTheProject()
    {
        $this->project->createScript('test', 'Test script description.')
            ->addStep('add-step', 'ls');

        $this->assertInstanceOf(
            '\DeltaCli\Script\Step\ShellCommand',
            $this->project->getScript('test')->getStep('add-step')
        );
    }

    public function testCanSetAndGetProjectName()
    {
        $input  = new ArgvInput();
        $output = new ConsoleOutput();

        /* @var $project \PHPUnit_Framework_MockObject_MockObject|Project */
        $project = $this->getMock(
            '\DeltaCli\Project',
            ['configFileExists'],
            [new Application(), $input, $output]
        );

        $project->expects($this->any())
            ->method('configFileExists')
            ->willReturn(false);

        $project->setName('Test');
        $this->assertEquals('Test', $project->getName());
    }

    public function testGetNameWillLoadProjectConfigFile()
    {
        $input  = new ArgvInput();
        $output = new ConsoleOutput();

        /* @var $project \PHPUnit_Framework_MockObject_MockObject|Project */
        $project = $this->getMock(
            '\DeltaCli\Project',
            ['configFileExists', 'loadConfigFile'],
            [new Application(), $input, $output]
        );

        $project->expects($this->any())
            ->method('configFileExists')
            ->willReturn(true);

        $project->expects($this->once())
            ->method('loadConfigFile');

        $project->getName();
    }

    public function testGetScriptsWillLoadProjectConfigFileAndReturnScriptsArray()
    {
        $input  = new ArgvInput();
        $output = new ConsoleOutput();

        /* @var $project \PHPUnit_Framework_MockObject_MockObject|Project */
        $project = $this->getMock(
            '\DeltaCli\Project',
            ['configFileExists', 'loadConfigFile'],
            [new Application(), $input, $output]
        );

        $project->expects($this->any())
            ->method('configFileExists')
            ->willReturn(true);

        $project->expects($this->once())
            ->method('loadConfigFile');

        $project->createScript('test', 'Script to test for.');

        $scripts = $project->getScripts();

        $this->assertTrue(is_array($scripts));

        $foundTestScript = false;

        /* @var $script Script */
        foreach ($scripts as $script) {
            if ('test' === $script->getName()) {
                $foundTestScript = true;
            }
        }

        $this->assertTrue($foundTestScript);
    }

    public function testCanCreateEnvironment()
    {
        $this->assertFalse($this->project->hasEnvironment('test'));
        $this->assertInstanceOf('\DeltaCli\Environment', $this->project->createEnvironment('test'));
        $this->assertTrue($this->project->hasEnvironment('test'));
        $this->assertInstanceOf('\DeltaCli\Environment', $this->project->getEnvironment('test'));
    }

    /**
     * @expectedException \DeltaCli\Exception\EnvironmentNotFound
     */
    public function testAttemptingToGetNonExistentEnvironmentThrowsException()
    {
        $this->project->getEnvironment('test');
    }

    /**
     * @expectedException \DeltaCli\Exception\ScriptNotFound
     */
    public function testAttemptingToGetNonExistentScriptThrowsException()
    {
        $this->project->getScript('test');
    }
}
