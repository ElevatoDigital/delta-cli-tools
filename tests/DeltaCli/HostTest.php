<?php

namespace DeltaCli;

use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class HostTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Host
     */
    private $host;

    public function setUp()
    {
        $project     = new Project(new ArgvInput(), new BufferedOutput());
        $environment = new Environment($project, 'test');

        $this->host = new Host('localhost', $environment);
    }

    public function testCanSetAndGetSshHomeFolder()
    {
        $this->assertNull($this->host->getSshHomeFolder());
        $this->assertInstanceOf('\DeltaCli\Host', $this->host->setSshHomeFolder('/tmp'));
        $this->assertEquals('/tmp', $this->host->getSshHomeFolder());
    }

    public function testCanSetAndGetCustomSshPort()
    {
        $this->assertEquals(22, $this->host->getSshPort());
        $this->assertInstanceOf('\DeltaCli\Host', $this->host->setSshPort(42));
        $this->assertEquals(42, $this->host->getSshPort());
    }
}
