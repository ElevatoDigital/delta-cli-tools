<?php

namespace DeltaCli;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ScriptTest extends PHPUnit_Framework_TestCase
{
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
}
