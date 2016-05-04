<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Extension\Vagrant\Exception;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckEnvironment extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:check-environment',
            'Ensure the user is currently in a working Delta Vagrant environment.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->addStep(
                'vagrant-delta-folder-exists',
                function () {
                    if (!file_exists('/delta') || !is_dir('/delta')) {
                        throw new Exception('/delta folder does not exist.');
                    }
                }
            )
            ->addStep(
                'vagrant-vhosts-folder-exists',
                function () {
                    if (!file_exists('/delta/vhost.d') || !is_dir('/delta/vhost.d')) {
                        throw new Exception('vhost.d folder does not exist.');
                    }
                }
            )
            ->addStep(
                'vagrant-sudo-without-password',
                function () {
                    if ('root' !== trim(shell_exec("echo '' | sudo -S whoami"))) {
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

                    exec($cmd, $output, $exitStatus);

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

                    exec($cmd, $output, $exitStatus);

                    if ($exitStatus || 'success' !== trim($output[0])) {
                        throw new Exception('Could not access Postgres superuser with no password.');
                    }
                }
            );

        return parent::execute($input, $output);
    }
}
