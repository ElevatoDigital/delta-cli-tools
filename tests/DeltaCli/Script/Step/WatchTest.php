<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Project;
use DeltaCli\Script as ScriptObject;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class WatchTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var Script
     */
    private $script;

    public function setUp()
    {
        $input  = new ArgvInput();
        $output = new BufferedOutput();

        $this->project = new Project(new Application(), $input, $output);
        $this->script  = new ScriptObject($this->project, 'watch', 'Watch test.');
    }

    /**
     * @expectedException \DeltaCli\Exception\NoOtherStepsCanBeAddedAfterWatch
     */
    public function testCannotAddStepsOtherThanWatchToScript()
    {
        $fileWatcher = $this->getFileWatcherMock();

        $watchStep = new Watch($this->script, $fileWatcher);
        $this->script
            ->addStep($watchStep)
            ->addStep('ls');
    }

    public function testCanAddMoreWatchStepsToScript()
    {
        $fileWatcher      = $this->getFileWatcherMock();
        $watchStep        = new Watch($this->script, $fileWatcher);
        $anotherWatchStep = new Watch($this->script, $fileWatcher);

        $watchStep->setName('one');
        $anotherWatchStep->setName('two');

        $this->script
            ->addStep($watchStep)
            ->addStep($anotherWatchStep);

        $this->assertInstanceOf('\DeltaCli\Script\Step\Watch', $this->script->getStep('one'));
        $this->assertInstanceOf('\DeltaCli\Script\Step\Watch', $this->script->getStep('two'));
    }

    public function testLoopIsStartedByThePostRunHook()
    {
        $fileWatcher = $this->getFileWatcherMock();
        $watchStep   = new Watch($this->script, $fileWatcher);

        $fileWatcher->expects($this->once())
            ->method('startLoop');

        $watchStep->postRun($this->script);
    }

    public function testWatchStepSupportsCustomNames()
    {
        $watchStep = new Watch($this->script, $this->getFileWatcherMock());

        $this->assertTrue(is_string($watchStep->getName()));

        $watchStep->setName('custom-name');
        $this->assertEquals('custom-name', $watchStep->getName());
    }

    public function testRunningWithNoPathsReturnsFailureResult()
    {
        $watchStep = new Watch($this->script, $this->getFileWatcherMock());
        $result    = $watchStep->run();

        $this->assertTrue($result->isFailure());
    }

    public function testRunAddsWatchToFileWatcher()
    {
        $fileWatcher = $this->getFileWatcherMock();
        $watchStep   = new Watch($this->script, $fileWatcher);

        $fileWatcher->expects($this->once())
            ->method('addWatch')
            ->with([__DIR__], $this->script, false, true);

        $watchStep
            ->addPath(__DIR__)
            ->setStopOnFailure(true)
            ->setOnlyNotifyOnFailure(false);

        $watchStep->run();
    }

    public function testCanAddMultiplePaths()
    {
        $fileWatcher = $this->getFileWatcherMock();
        $watchStep   = new Watch($this->script, $fileWatcher);

        $fileWatcher->expects($this->once())
            ->method('addWatch')
            ->with([__DIR__, 'path-two'], $this->script, false, true);

        $watchStep
            ->addPaths([__DIR__, 'path-two'])
            ->setStopOnFailure(true)
            ->setOnlyNotifyOnFailure(false);

        $watchStep->run();
    }

    public function testCanSetSelectedEnvironment()
    {
        $fileWatcher = $this->getFileWatcherMock();
        $watchStep   = new Watch($this->script, $fileWatcher);

        $watchStep->setSelectedEnvironment(new Environment($this->project, 'test'));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\DeltaCli\FileWatcher\FileWatcherInterface
     */
    private function getFileWatcherMock()
    {
        return $this->getMock('\DeltaCli\FileWatcher\FileWatcherInterface');
    }
}
