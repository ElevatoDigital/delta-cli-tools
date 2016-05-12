<?php

namespace DeltaCli;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ProjectText extends PHPUnit_Framework_TestCase
{
    /**
     * @var Project
     */
    private $project;

    public function setUp()
    {
        $input  = new ArgvInput();
        $output = new ConsoleOutput();

        $this->project = new Project($input, $output);
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
}
