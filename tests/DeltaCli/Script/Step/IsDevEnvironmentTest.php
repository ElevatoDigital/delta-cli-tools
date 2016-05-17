<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Project;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class IsDevEnvironmentTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var IsDevEnvironment
     */
    private $step;

    public function __construct()
    {
        parent::__construct();

        $this->project = new Project(new ArgvInput(), new BufferedOutput());
        $this->step    = new IsDevEnvironment();
    }

    public function testSupportsCustomNames()
    {
        $this->assertTrue(is_string($this->step->getName()));

        $this->step->setName('custom-name');
        $this->assertEquals('custom-name', $this->step->getName());
    }

    /**
     * @expectedException \DeltaCli\Exception\EnvironmentNotAvailableForStep
     */
    public function testRunningWithNoEnvironmentThrowsException()
    {
        $this->step->run();
    }

    public function testRunningWithDevEnvironmentReturnsSuccessfulResult()
    {
        $environment = new Environment($this->project, 'dev');
        $environment->setIsDevEnvironment(true);
        $this->step->setSelectedEnvironment($environment);
        $this->assertFalse($this->step->run()->isFailure());
        $this->assertFalse($this->step->dryRun()->isFailure());
    }

    public function testRunningWithANonDevEnvironmentReturnsFailure()
    {
        $environment = new Environment($this->project, 'production');
        $this->step->setSelectedEnvironment($environment);
        $this->assertTrue($this->step->run()->isFailure());
        $this->assertTrue($this->step->dryRun()->isFailure());
    }
}
