<?php

namespace DeltaCli;

class SshCommand
{
    const INCLUDE_APPLICATION_ENV = true;

    const OMIT_APPLICATION_ENV = false;

    /**
     * @var Host
     */
    private $host;

    /**
     * @var string
     */
    private $command;

    /**
     * @var string
     */
    private $additionalFlags;

    /**
     * @var bool
     */
    private $isTunnel;

    /**
     * @var Host
     */
    private $tunnelHost;

    /**
     * @var bool
     */
    private $includeApplicationEnv = true;

    /**
     * @var string
     */
    private $agentEnvironmentVars;

    public function __construct(Host $host)
    {
        $this->host = $host;
    }

    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    public function setAdditionalFlags($additionalFlags)
    {
        $this->additionalFlags = $additionalFlags;

        return $this;
    }

    public function setIsTunnel($isTunnel)
    {
        $this->isTunnel = $isTunnel;

        return $this;
    }

    public function setTunnelHost(Host $tunnelHost)
    {
        $this->tunnelHost = $tunnelHost;

        return $this;
    }

    public function getAgentEnvironmentVars()
    {
        if (!$this->isTunnel || $this->tunnelHost) {
            return '';
        } else {
            if (!$this->agentEnvironmentVars) {
                $this->agentEnvironmentVars = $this->executeSshAgentAndAddIdentity();
            }

            return $this->agentEnvironmentVars;
        }
    }

    public function setIncludeApplicationEnv($includeApplicationEnv)
    {
        $this->includeApplicationEnv = $includeApplicationEnv;

        return $this;
    }

    public function includeApplicationEnv()
    {
        $this->includeApplicationEnv = self::INCLUDE_APPLICATION_ENV;

        return $this;
    }

    public function omitApplicationEnv()
    {
        $this->includeApplicationEnv = self::OMIT_APPLICATION_ENV;

        return $this;
    }

    public function getApplicationEnv()
    {
        return sprintf(
            'export APPLICATION_ENV=%s; ',
            escapeshellarg($this->host->getEnvironment()->getName())
        );
    }

    public function getCommand()
    {
        $keyFlag = '';

        if ($this->host->getSshPrivateKey() && !$this->tunnelHost) {
            $keyFlag = '-i ' . escapeshellarg($this->host->getSshPrivateKey());
        }

        $command = sprintf(
            '%sssh -p %s %s %s %s %s@%s %s',
            ($this->includeApplicationEnv ? $this->getApplicationEnv() : ''),
            escapeshellarg($this->host->getSshPort()),
            ($this->isTunnel ? '-A' : ''),
            $this->additionalFlags,
            $keyFlag,
            escapeshellarg($this->host->getUsername()),
            escapeshellarg($this->host->getHostname()),
            (null === $this->command ? '' : escapeshellarg($this->command))
        );

        return $command;
    }

    public function getWrappedCommand()
    {
        if (!$this->tunnelHost) {
            return $this;
        } else {
            return $this->tunnelHost->assembleSshCommand($this->getCommand(), $this->includeApplicationEnv, true)
                ->setAdditionalFlags($this->additionalFlags);
        }
    }

    public function __toString()
    {
        return $this->getAgentEnvironmentVars() . $this->getCommand();
    }

    private function executeSshAgentAndAddIdentity()
    {
        if (!$this->host->getSshPrivateKey()) {
            return '';
        }

        // Run ssh-agent and get environment variable output
        exec('ssh-agent 2>&1', $output);
        $environmentVars = $output[0] . $output[1];

        // Add identity to agent
        exec(sprintf('%s ssh-add %s 2>&1', $environmentVars, $this->host->getSshPrivateKey()));

        return $environmentVars;
    }
}
