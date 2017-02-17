<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Console\Output\Spinner;
use DeltaCli\Environment;
use DeltaCli\Project;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RsyncTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Rsync
     */
    private $step;

    public function setUp()
    {
        $output  = new BufferedOutput();
        $project = new Project(new Application(), new ArgvInput(), $output);

        Spinner::setDefaultOutput($output);

        $this->environment = new Environment($project, 'test');
        $this->step        = new Rsync(__DIR__, '/path/to/remote-folder');

        $this->environment
            ->setUsername('test')
            ->addHost('localhost');

        $this->step->setSelectedEnvironment($this->environment);
    }

    public function testSupportsCustomNames()
    {
        $this->assertInstanceOf('\DeltaCli\Script\Step\Rsync', $this->step->setName('custom-name'));
        $this->assertEquals('custom-name', $this->step->getName());
    }

    public function testDefaultNameIncludesLocalAndRemotePath()
    {
        $this->assertContains('step', $this->step->getName());
        $this->assertContains('remote-folder', $this->step->getName());
    }

    public function testDefaultNameUsesSlugify()
    {
        /* @var $slugify \Cocur\Slugify\Slugify|\PHPUnit_Framework_MockObject_MockObject */
        $slugify = $this->getMock(
            '\Cocur\Slugify\Slugify',
            ['slugify']
        );

        $slugify->expects($this->once())
            ->method('slugify');

        $this->step->setSlugify($slugify);

        $this->step->getName();
    }

    public function testDefaultFlagsArePresent()
    {
        $this->expectedCommandFlagIsPresent('-az --no-p');
    }

    public function testCanSetCustomFlags()
    {
        $this->step->setFlags('-customflag');

        $this->expectedCommandFlagIsPresent('-customflag');
    }

    public function testCustomExcludesAreAddedAsCommandFlags()
    {
        $this->step
            ->exclude('one')
            ->exclude('two');

        $this->expectedCommandFlagIsPresent("--exclude='one'");
        $this->expectedCommandFlagIsPresent("--exclude='two'");
    }

    public function testDryRunAddsDryRunFlag()
    {
        $this->step->setCommandRunner(
            function ($command, &$output, &$exitStatus) {
                $this->assertNotContains(' --dry-run ', $command);

                $output     = [];
                $exitStatus = 0;
            }
        );

        $this->step->run();

        $this->step->setCommandRunner(
            function ($command, &$output, &$exitStatus) {
                $this->assertContains(' --dry-run ', $command);

                $output     = [];
                $exitStatus = 0;
            }
        );

        $this->step->dryRun();
    }

    public function testDeleteAddsDeleteCommandFlag()
    {
        $this->expectedCommandFlagIsNotPresent('--delete');
        $this->step->delete();
        $this->expectedCommandFlagIsPresent('--delete');
    }

    public function testExcludingVcsAddsGitAndCvsExcludeFlags()
    {
        $this->step->includeVcs();

        $this->expectedCommandFlagIsNotPresent('--exclude=.git');
        $this->expectedCommandFlagIsNotPresent('--cvs-exclude');

        $this->step->excludeVcs();

        $this->expectedCommandFlagIsPresent('--exclude=.git');
        $this->expectedCommandFlagIsPresent('--cvs-exclude');
    }

    public function testExcludingOsCruftAddsMoreExcludes()
    {
        $this->step->includeOsCruft();

        $this->expectedCommandFlagIsNotPresent('--exclude=.DS_Store');

        $this->step->excludeOsCruft();

        $this->expectedCommandFlagIsPresent('--exclude=.DS_Store');
    }

    private function expectedCommandFlagIsNotPresent($missingFlag)
    {
        $this->step->setCommandRunner(
            function ($command, &$output, &$exitStatus) use ($missingFlag) {
                $this->assertNotContains(' ' . $missingFlag . ' ', $command);

                $output     = [];
                $exitStatus = 0;
            }
        );

        $this->step->run();
    }

    private function expectedCommandFlagIsPresent($expectedFlag)
    {
        $this->step->setCommandRunner(
            function ($command, &$output, &$exitStatus) use ($expectedFlag) {
                $this->assertContains(' ' . $expectedFlag . ' ', $command);

                $output     = [];
                $exitStatus = 0;
            }
        );

        $this->step->run();
    }
}
