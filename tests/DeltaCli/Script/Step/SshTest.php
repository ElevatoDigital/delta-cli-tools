<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Environment;
use DeltaCli\Project;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SshTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Ssh
     */
    private $step;

    /**
     * @var Environment
     */
    private $environment;

    public function setUp()
    {
        $this->step = new Ssh('ls');

        $project = new Project(new ArgvInput(), new BufferedOutput());
        $this->environment = new Environment($project, 'test');

        $this->environment
            ->setUsername('test')
            ->addHost('localhost');

        $this->step->setSelectedEnvironment($this->environment);
    }

    public function testSupportsCustomNames()
    {
        $this->assertTrue(is_string($this->step->getName()));
        $this->assertInstanceOf('\DeltaCli\Script\Step\Ssh', $this->step->setName('custom-name'));
        $this->assertEquals('custom-name', $this->step->getName());
    }

    public function testUsesSlugifyForDefaultName()
    {
        /* @var $slugify \Cocur\Slugify\Slugify|\PHPUnit_Framework_MockObject_MockObject */
        $slugify = $this->getMock(
            '\Cocur\Slugify\Slugify',
            ['slugify']
        );

        $this->step->setSlugify($slugify);

        $slugify->expects($this->once())
            ->method('slugify');

        $this->step->getName();
    }

    public function testCommandRunnerIsUsedToRunCommand()
    {
        $commandRunnerCalled = true;

        $this->step->setCommandRunner(
            function ($command, &$output, &$exitStatus) use (&$commandRunnerCalled) {
                $commandRunnerCalled = true;
                $output = ['test'];
            }
        );

        $this->step->run();

        $this->assertTrue($commandRunnerCalled);
    }

    public function testSshTunnelIsSetUpAndTornDown()
    {
        /* @var $mockTunnel \DeltaCli\SshTunnel|\PHPUnit_Framework_MockObject_MockObject */
        $mockTunnel = $this->getMock(
            '\DeltaCli\SshTunnel',
            ['setUp', 'tearDown'],
            [$this->environment->getHost('localhost')]
        );

        $mockTunnel->expects($this->once())
            ->method('setUp');

        $mockTunnel->expects($this->once())
            ->method('tearDown');

        $this->environment->getHost('localhost')
            ->setSshTunnel($mockTunnel);

        $this->step->setCommandRunner(
            function ($command, &$output, &$exitStatus) use (&$commandRunnerCalled) {

            }
        );

        $this->step->run();
    }

    public function testSshTunnelIsUsedToAssembleSshCommand()
    {
        /* @var $mockTunnel \DeltaCli\SshTunnel|\PHPUnit_Framework_MockObject_MockObject */
        $mockTunnel = $this->getMock(
            '\DeltaCli\SshTunnel',
            ['assembleSshCommand'],
            [$this->environment->getHost('localhost')]
        );

        $mockTunnel->expects($this->once())
            ->method('assembleSshCommand')
            ->with('ls');

        $this->environment->getHost('localhost')->setSshTunnel($mockTunnel);

        $this->step->setCommandRunner(
            function ($command, &$output, &$exitStatus) {
                $output = ['test'];
            }
        );

        $this->step->run();
    }

    public function testRunOnHostReturnsOutputAndExitStatus()
    {
        $commandRunnerCalled = true;

        $this->step->setCommandRunner(
            function ($command, &$output, &$exitStatus) use (&$commandRunnerCalled) {
                $output     = ['test'];
                $exitStatus = 42;
            }
        );

        $this->step->run();

        list($output, $exitStatus) = $this->step->runOnHost($this->environment->getHost('localhost'));

        $this->assertEquals(['test'], $output);
        $this->assertEquals(42, $exitStatus);
    }
}
