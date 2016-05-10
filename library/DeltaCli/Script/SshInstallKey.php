<?php

namespace DeltaCli\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Exception;

class SshInstallKey extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'ssh:install-key',
            'Install your SSH public key in the authorized_keys file on a remote environment.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();
        parent::configure();
    }

    protected function addSteps()
    {
        $publicKey = getcwd() . '/ssh-keys/id_rsa.pub';

        $this
            ->addStep($this->getProject()->getScript('ssh:fix-key-permissions'))
            ->addStep(
                'check-for-public-key',
                function () use ($publicKey) {
                    if (!file_exists($publicKey)) {
                        throw new Exception('SSH keys have not been generated.  Run ssh:generate-keys.');
                    }

                    /* @var $host \DeltaCli\Host */
                    foreach ($this->getEnvironment()->getHosts() as $host) {
                        $this->getProject()->getOutput()->writeln(
                            sprintf(
                                '<comment>You will be prompted for the SSH password for %s@%s several times during the '
                                . 'installation.</comment>',
                                $host->getUsername(),
                                $host->getHostname()
                            )
                        );
                    }
                }
            )
            ->addStep($this->getProject()->scp($publicKey, ''))
            ->addStep($this->getProject()->ssh('mkdir -p .ssh')->setName('create-ssh-folder'))
            ->addStep(
                $this->getProject()->ssh('touch .ssh/authorized_keys && chmod +w .ssh/authorized_keys')
                    ->setName('allow-authorized-keys-writes')
            )
            ->addStep($this->getProject()->ssh('cat id_rsa.pub >> .ssh/authorized_keys')->setName('add-key'))
            ->addStep(
                $this->getProject()->ssh('chmod 400 .ssh/authorized_keys')
                    ->setName('change-authorized-keys-permissions')
            )
            ->addStep(
                $this->getProject()->ssh('chmod 700 .ssh')
                    ->setName('change-ssh-folder-permissions')
            );
    }
}
