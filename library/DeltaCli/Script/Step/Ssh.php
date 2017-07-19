<?php

namespace DeltaCli\Script\Step;

use Cocur\Slugify\Slugify;
use DeltaCli\Console\Output\Spinner;
use DeltaCli\Exception\InvalidSshCommandMode;
use DeltaCli\Host;
use DeltaCli\Script as ScriptObject;
use Symfony\Component\Console\Output\OutputInterface;

class Ssh extends EnvironmentHostsStepAbstract implements DryRunInterface
{
    const LIVE = 'live';

    const DRY_RUN = 'dry-run';

    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $dryRunCommand;

    /**
     * @var Slugify
     */
    private $slugify;

    private $stdIn;

    private $mode = self::LIVE;

    public function __construct($command, $dryRunCommand = null)
    {
        $this->command       = $command;
        $this->dryRunCommand = $dryRunCommand;
    }

    public function setStdIn($stdIn)
    {
        $this->stdIn = $stdIn;

        return $this;
    }

    public function setSlugify(Slugify $slugify)
    {
        $this->slugify = $slugify;

        return $this;
    }

    public function getSlugify()
    {
        if (!$this->slugify) {
            $this->slugify = new Slugify();
        }

        return $this->slugify;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return sprintf(
                'run-%s-over-ssh-on-%s',
                $this->getSlugify()->slugify($this->command),
                $this->environment->getName()
            );
        }
    }

    public function run()
    {
        $this->mode = self::LIVE;

        return $this->runOnAllHosts();
    }

    public function dryRun()
    {
        if (!$this->dryRunCommand) {
            $result = new Result($this, Result::SKIPPED);
            $result->setExplanation('because no dry run version of the command is available');
            return $result;
        }

        $this->mode = self::DRY_RUN;

        return $this->runOnAllHosts();
    }

    public function preRun(ScriptObject $script)
    {
        $this->checkIfExecutableExists('ssh', 'ssh -V');
    }

    public function runOnHost(Host $host)
    {
        if (self::LIVE === $this->mode) {
            $command = $this->command;
        } elseif (self::DRY_RUN === $this->mode) {
            $command = $this->dryRunCommand;
        } else {
            throw new InvalidSshCommandMode("Mode must be live or dry-run.  '{$this->mode}' given.");
        }

        $tunnel = $host->getSshTunnel();
        $tunnel->setUp();
        $this->execSsh(
            $host,
            $tunnel->assembleSshCommand($command, '', true, $this->stdIn),
            $output,
            $exitStatus,
            Spinner::forStep($this, $host)
        );
        $tunnel->tearDown();

        return [$output, $exitStatus];
    }
}
