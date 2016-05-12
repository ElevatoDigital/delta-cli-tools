<?php

namespace DeltaCli\Script\Step;

use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;

class ShellCommandSupportingDryRunTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ShellCommandSupportingDryRun|PHPUnit_Framework_MockObject_MockObject
     */
    private $step;

    public function setUp()
    {
        $this->step = $this->getMock(
            '\DeltaCli\Script\Step\ShellCommandSupportingDryRun',
            ['exec'],
            ['main', 'dry-run']
        );

        $this->step->setCaptureStdErr(false);
    }

    public function testMainCommandIsCalledByRun()
    {
        $this->step->expects($this->once())
            ->method('exec')
            ->with('main');

        $this->step->run();
    }
    
    public function testDryRunCommandIsCalledByDryRun()
    {
        $this->step->expects($this->once())
            ->method('exec')
            ->with('dry-run');

        $this->step->dryRun();
    }
}
