<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Cache;
use DeltaCli\Exec;
use DeltaCli\Extension\Vagrant\Exception;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\SshTunnel;

class CheckEnvironment extends Script
{
    /**
    * @var Cache
     */
    private $cache;

    /**
    * @var SshTunnel
     */
    private $sshTunnel;

    public function __construct(Project $project, Cache $cache)
    {
        $this->cache = $cache;

        parent::__construct(
            $project,
            'vagrant:check-environment',
            'Ensure the user is currently in a working Delta Vagrant environment.'
        );
    }

    protected function addSteps()
    {
        $this
            ->addStep(
                'vagrant-delta-folder-exists',
                function () {
                    $defaultDirLocation = '/delta';
                    $dirLocation = $this->cache->fetch('synced-dir-path') ? $this->cache->fetch('synced-dir-path') : $defaultDirLocation;

                    if (!file_exists($dirLocation) || !is_dir($dirLocation)) {
                        throw new Exception(
                            sprintf('Designated synced directory (%s) does not exist. Default is %s. Use vagrant:set-synced-dir-path for custom implementation.',
                            $dirLocation,
                            $defaultDirLocation
                        ));
                    } else {
                        $this->cache->store('synced-dir-path', $dirLocation);
                    }
                }
            )
            ->addStep(
                'vagrant-vhosts-folder-exists',
                function () {
                    if (!file_exists($this->cache->fetch('synced-dir-path') . '/vhost.d') || !is_dir($this->cache->fetch('synced-dir-path') . '/vhost.d')) {
                        throw new Exception('vhost.d folder does not exist.');
                    }
                }
            )
            ->addStep(
                'vagrant-sudo-without-password',
                function () {
                    $cmd = "echo '' | sudo -S whoami";

                    Exec::run($this->sshTunnel->assembleSshCommand($cmd), $output, $exitStatus);

                    if ('root' !== trim($output[0])) {
                        throw new Exception('Could not run sudo without password.');
                    }
                }
            )
            ->addStep(
                'vagrant-mysql-root-with-default-password',
                function () {
                    $cmd = sprintf(
                        'echo %s | mysql --user=root --password=delta 2>&1',
                        escapeshellarg("SELECT 'success';")
                    );

                    Exec::run($this->sshTunnel->assembleSshCommand($cmd), $output, $exitStatus);

                    if ($exitStatus || 'success' !== trim($output[0])) {
                        throw new Exception('Could not access MySQL with default Vagrant password (delta).');
                    }
                }
            )
            ->addStep(
                'vagrant-postgres-superuser-with-no-password',
                function () {
                    $cmd = sprintf(
                        'echo %s | psql -t -U postgres 2>&1',
                        escapeshellarg("SELECT 'success';")
                    );

                    Exec::run($this->sshTunnel->assembleSshCommand($cmd), $output, $exitStatus);

                    if ($exitStatus || 'success' !== trim($output[0])) {
                        throw new Exception('Could not access Postgres superuser with no password.');
                    }
                }
            );
    }

    protected function preRun()
    {
        $this->setEnvironment('vagrant');

        $this->sshTunnel = $this->getEnvironment()->getHost('127.0.0.1')->getSshTunnel();
        $this->sshTunnel->setUp();
    }

    protected function postRun()
    {
        $this->sshTunnel->tearDown();
    }
}
