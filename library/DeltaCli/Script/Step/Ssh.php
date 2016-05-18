<?php

namespace DeltaCli\Script\Step;

use Cocur\Slugify\Slugify;
use DeltaCli\Exec;
use DeltaCli\Host;

class Ssh extends EnvironmentHostsStepAbstract
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var Slugify
     */
    private $slugify;

    public function __construct($command)
    {
        $this->command = $command;
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
            return $this->getSlugify()->slugify($this->command . '-over-ssh');
        }
    }

    public function runOnHost(Host $host)
    {
        $tunnel = $host->getSshTunnel();
        $tunnel->setUp();
        $this->exec($tunnel->assembleSshCommand($this->command), $output, $exitStatus);
        $tunnel->tearDown();

        return [$output, $exitStatus];
    }
}
