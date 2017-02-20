<?php

namespace DeltaCli;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnvironmentTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var BufferedOutput
     */
    private $output;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Project
     */
    private $project;

    public function setUp()
    {
        $input = new ArgvInput();

        $this->output  = new BufferedOutput();
        $this->project = new Project(new Application(), $input, $this->output);

        $this->environment = new Environment($this->project, 'test');
    }

    public function testCanGetName()
    {
        $this->assertEquals('test', $this->environment->getName());
    }

    public function testCanSetAndGetIsDevEnvironmentFlag()
    {
        $this->assertFalse($this->environment->isDevEnvironment());
        $this->assertFalse($this->environment->getIsDevEnvironment());
        $this->assertInstanceOf('\DeltaCli\Environment', $this->environment->setIsDevEnvironment(true));
        $this->assertTrue($this->environment->isDevEnvironment());
        $this->assertTrue($this->environment->getIsDevEnvironment());
    }

    public function testCanSetAndGetGitBranch()
    {
        $this->environment->setGitBranch('master');
        $this->assertEquals('master', $this->environment->getGitBranch());
    }

    public function testCanSetAndGetUsername()
    {
        $this->environment->setUsername('user');
        $this->assertEquals('user', $this->environment->getUsername());
    }

    public function testCanSetAndGetSshPrivateKey()
    {
        $this->environment->setSshPrivateKey('id_rsa');
        $this->assertEquals('id_rsa', $this->environment->getSshPrivateKey());
    }

    public function testCanAddHosts()
    {
        $this->environment->addHost('hostname');
        $this->assertInstanceOf('\DeltaCli\Host', $this->environment->getHost('hostname'));
    }

    public function testGettingMissingHostReturnsFalse()
    {
        $this->assertFalse($this->environment->getHost('not-there'));
    }

    public function testCanGetAllHosts()
    {
        $this->environment
            ->addHost('one')
            ->addHost('two');

        $this->assertEquals(2, count($this->environment->getHosts()));
    }

    public function testTunnelingSshViaWithSingleHostEnvironmentAutomaticallyAssignsThatHost()
    {
        $this->project->createEnvironment('other-environment')
            ->addHost('single-host');

        $this->environment->tunnelSshVia('other-environment');

        $this->assertEquals('single-host', $this->environment->getTunnelHost()->getHostname());
    }

    /**
     * @expectedException \DeltaCli\Exception\MustSpecifyHostnameForShell
     */
    public function testTunnelingSshViaWithMultiHostEnvironmentThrowsExceptionWithNoHostname()
    {
        $this->project->createEnvironment('other-environment')
            ->addHost('first-host')
            ->addHost('second-host');

        $this->environment->tunnelSshVia('other-environment');
    }

    public function testTunnelingSshViaMultiHostEnvironmentWithValidHostnameSetsTunnelHost()
    {
        $this->project->createEnvironment('other-environment')
            ->addHost('first-host')
            ->addHost('second-host');

        $this->environment->tunnelSshVia('other-environment', 'second-host');

        $this->assertEquals('second-host', $this->environment->getTunnelHost()->getHostname());
    }

    /**
     * @expectedException \DeltaCli\Exception\HostNotFound
     */
    public function testTunnelingSshViaMultiHostEnvironmentWithInvalidHostnameThrowsException()
    {
        $this->project->createEnvironment('other-environment')
            ->addHost('first-host')
            ->addHost('second-host');

        $this->environment->tunnelSshVia('other-environment', 'third-host');
    }

    /**
     * @expectedException \DeltaCli\Exception\EnvironmentNotFound
     */
    public function testTunnelingSshViaInvalidEnvironmentThrowsException()
    {
        $this->environment->tunnelSshVia(new \stdClass());
    }
}
