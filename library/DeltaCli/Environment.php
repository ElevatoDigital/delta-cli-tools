<?php

namespace DeltaCli;

use DeltaCli\Config\Config;
use DeltaCli\Environment\NotebookHtmlRenderer;
use DeltaCli\Environment\ResourceRenderer;
use DeltaCli\Exception\EnvironmentNotFound;
use DeltaCli\Exception\HostNotFound;
use DeltaCli\Exception\MustSpecifyHostnameForShell;
use Symfony\Component\Console\Output\OutputInterface;

class Environment
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $applicationEnv;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $sshPrivateKey;

    /**
     * @var Host[]
     */
    private $hosts = [];

    /**
     * @var string
     */
    private $gitBranch;

    /**
     * @var Host
     */
    private $tunnelHost;

    /**
     * @var bool
     */
    private $isDevEnvironment = false;

    /**
     * @var bool
     */
    private $logAndSendNotifications;

    /**
     * @var Config
     */
    private $manualConfig;

    public function __construct(Project $project, $name)
    {
        $this->project      = $project;
        $this->name         = $name;
        $this->manualConfig = new Config();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setManualConfig(Config $config)
    {
        $this->manualConfig = $config;

        return $this;
    }

    public function getManualConfig()
    {
        return $this->manualConfig;
    }

    public function getApplicationEnv()
    {
        if (null === $this->applicationEnv) {
            return $this->name;
        } else {
            return $this->applicationEnv;
        }
    }

    public function setApplicationEnv($applicationEnv)
    {
        $this->applicationEnv = $applicationEnv;

        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setSshPrivateKey($sshPrivateKey)
    {
        $this->sshPrivateKey = $sshPrivateKey;

        return $this;
    }

    public function getSshPrivateKey()
    {
        return $this->sshPrivateKey;
    }

    public function setIsDevEnvironment($isDevEnvironment)
    {
        $this->isDevEnvironment = $isDevEnvironment;

        return $this;
    }

    public function isDevEnvironment()
    {
        return $this->isDevEnvironment;
    }

    public function getIsDevEnvironment()
    {
        return $this->isDevEnvironment;
    }

    public function setLogAndSendNotifications($logAndSendNotifications)
    {
        $this->logAndSendNotifications = (boolean) $logAndSendNotifications;

        return $this;
    }

    public function getLogAndSendNotifications()
    {
        if (null === $this->logAndSendNotifications) {
            return ($this->isDevEnvironment() ? false : true);
        } else {
            return $this->logAndSendNotifications;
        }
    }

    public function addHost($hostname, $username = null, $sshPrivateKey = null)
    {
        $host = new Host($hostname, $this);

        $host
            ->setUsername($username)
            ->setSshPrivateKey($sshPrivateKey);

        $this->hosts[] = $host;

        return $this;
    }

    /**
     * @return Host[]
     */
    public function getHosts()
    {
        return $this->hosts;
    }

    public function getHost($hostname)
    {
        /* @var $host Host */
        foreach ($this->hosts as $host) {
            if ($host->getHostname() === $hostname) {
                return $host;
            }
        }

        return false;
    }

    /**
     * @param string $userInput
     * @return Host
     * @throws MustSpecifyHostnameForShell
     */
    public function getSelectedHost($userInput)
    {
        if (1 === count($this->hosts)) {
            $selected = reset($this->hosts);
        } else {
            if (!$userInput) {
                $hostCount = count($this->hosts);

                $hostList = [];
                foreach($this->hosts as $host) {
                    $hostList[] = "--hostname=" . $host->getHostname();
                }

                throw new MustSpecifyHostnameForShell(
                    "The {$this->getName()} environment has {$hostCount} hosts, so you must "
                    . "specify which host you'd like to shell into with the hostname option."
                    . PHP_EOL . implode(PHP_EOL, $hostList)
                );
            }

            $selected = [];

            /* @var $host Host */
            foreach ($this->hosts as $host) {
                if (false !== strpos($host->getHostname(), $userInput)) {
                    $selected[] = $host;
                }
            }

            if (!count($selected)) {
                throw new MustSpecifyHostnameForShell("No host could be found with the hostname {$userInput}.");
            } elseif (1 < count($selected)) {
                throw new MustSpecifyHostnameForShell("More than one host matches the hostname {$userInput}.");
            }

            $selected = current($selected);
        }

        if (!$selected->hasRequirementsForSshUse()) {
            throw new MustSpecifyHostnameForShell(
                "The {$selected->getHostname()} host is not configured for SSH which needs a username and hostname."
            );
        }

        return $selected;
    }

    public function setGitBranch($gitBranch)
    {
        $this->gitBranch = $gitBranch;

        return $this;
    }

    public function getGitBranch()
    {
        return $this->gitBranch;
    }

    public function getTunnelHost()
    {
        return $this->tunnelHost;
    }

    public function tunnelSshVia($environment, $hostname = null)
    {
        if (is_string($environment)) {
            $environment = $this->project->getEnvironment($environment);
        }

        if (!$environment instanceof Environment) {
            throw new EnvironmentNotFound("Invalid environment provided for SSH tunneling.");
        }

        $hosts = $environment->getHosts();

        if (1 === count($hosts)) {
            $this->tunnelHost = current($hosts);
        } elseif (!$hostname) {
            throw new MustSpecifyHostnameForShell(
                'When tunneling via an environment with more than one host, you must specify a host when '
                . 'calling tunnelSshVia().'
            );
        } else {
            $host = $environment->getHost($hostname);

            if (false === $host) {
                throw new HostNotFound(
                    "No host named {$hostname} could be found on the {$environment->getName()} environment."
                );
            }

            $this->tunnelHost = $host;
        }

        return $this;
    }

    public function displayResources(OutputInterface $output)
    {
        $renderer = new ResourceRenderer($this, $output);
        $renderer->render();
    }

    public function displayNotebookHtml(OutputInterface $output)
    {
        $renderer = new NotebookHtmlRenderer($this, $output);
        $renderer->render();
    }
}
