<?php

namespace DeltaCli;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DebugTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Debug::resetSingletonInstance();

    }
    public function testCanCreateSingletonInstanceAndThenLogStatically()
    {
        $output = new BufferedOutput();
        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
        Debug::createSingletonInstance($output);

        Debug::log('Test via singleton');

        $this->assertContains('Test via singleton', $output->fetch());
    }

    public function testCallingLogStaticallyWithoutInstanceReturnsSilently()
    {
        $this->assertNull(Debug::log('No instance.'));
    }

    public function testLoggingWhenVerbosityIsNotDebugNeverWrites()
    {
        /* @var $output \Symfony\Component\Console\Output\OutputInterface|\PHPUnit_Framework_MockObject_MockObject */
        $output = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');

        $output->expects($this->once())
            ->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_VERBOSE);

        $output->expects($this->never())
            ->method('writeln');

        $debug = new Debug($output);

        $debug->writeLog('Test message.');
    }
}
