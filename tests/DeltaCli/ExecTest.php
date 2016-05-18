<?php

namespace DeltaCli;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ExecTest extends PHPUnit_Framework_TestCase
{
    public function testRunningCommandWritesToDebugLog()
    {
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        Debug::createSingletonInstance($output);

        Exec::run('ls', $commandOutput, $exitStatus);

        $this->assertContains('ls', $output->fetch());

        Exec::resetInstance();
    }
}
