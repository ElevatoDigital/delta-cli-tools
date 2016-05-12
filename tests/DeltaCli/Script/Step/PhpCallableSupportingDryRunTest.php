<?php

namespace DeltaCli\Script\Step;

use PHPUnit_Framework_TestCase;

class PhpCallableSupportingDryRunTest extends PHPUnit_Framework_TestCase
{
    public function testMainCallableIsCalledByRun()
    {
        $mainCalled   = false;
        $dryRunCalled = false;

        $main = function () use (&$mainCalled) {
            $mainCalled = true;
        };

        $dryRun = function () use (&$dryRunCalled) {
            $dryRunCalled = true;
        };

        $step = new PhpCallableSupportingDryRun($main, $dryRun);
        $step->run();

        $this->assertTrue($mainCalled);
        $this->assertFalse($dryRunCalled);
    }

    public function testDryRunCallableIsCalledByDryRun()
    {
        $mainCalled   = false;
        $dryRunCalled = false;

        $main = function () use (&$mainCalled) {
            $mainCalled = true;
        };

        $dryRun = function () use (&$dryRunCalled) {
            $dryRunCalled = true;
        };

        $step = new PhpCallableSupportingDryRun($main, $dryRun);
        $step->dryRun();

        $this->assertFalse($mainCalled);
        $this->assertTrue($dryRunCalled);
    }
}
