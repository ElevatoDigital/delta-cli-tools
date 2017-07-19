<?php

namespace DeltaCli\Extension\WordPress\Script\Step;

use DeltaCli\Host;
use DeltaCli\Script\Step\EnvironmentHostsStepAbstract;
use DeltaCli\Script\Step\Ssh;

class WpCli extends EnvironmentHostsStepAbstract
{
    /**
     * @var array
     */
    private $args;

    public function __construct(array $args)
    {
        $this->args = $args;
    }

    public function runOnHost(Host $host)
    {
        $ssh = new Ssh('php');
        $ssh
            ->setName('install-wp-cli')
            ->setSelectedEnvironment($this->environment)
            ->setStdIn(__DIR__ . '/remote-scripts/install-wp-cli.php');
        $result = $ssh->runOnHost($host);

        list($output, $exitStatus) = $result;

        if ($exitStatus) {
            return $result;
        }

        $wpCli = current($output);

        $cmd = sprintf(
            '%s %s',
            $wpCli,
            implode(' ', $this->args)
        );

        $ssh = new Ssh($cmd);
        $ssh
            ->setName('install-wp-cli')
            ->setSelectedEnvironment($this->environment);
        return $ssh->runOnHost($host);
    }

    public function getName()
    {
        return 'run-wp-cli-command';
    }
}