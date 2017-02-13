<?php

namespace DeltaCli\Script;

use DeltaCli\Exec;
use DeltaCli\Extension\Vagrant as VagrantExtension;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\VagrantFinder;
use Exception;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

class SshInstallKey extends Script
{
    private $password;

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

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    protected function addSteps()
    {
        $publicKey = getcwd() . '/ssh-keys/id_rsa.pub';

        $this
            ->addStep(
                'apply-ssh-password',
                function () {
                    if ($this->password) {
                        foreach ($this->getEnvironment()->getHosts() as $host) {
                            $host->setSshPassword($this->password);
                            $host->getSshTunnel()->setBatchMode(false);
                        }
                        return;
                    }

                    /* @var $helper QuestionHelper */
                    $helper = $this->getHelper('question');
                    $input  = $this->getProject()->getInput();
                    $output = $this->getProject()->getOutput();

                    foreach ($this->getEnvironment()->getHosts() as $host) {
                        $question = new Question(
                            "<question>What is the SSH password for {$host->getUsername()}@{$host->getHostname()}?"
                            . "</question>\n"
                        );

                        $question
                            ->setHidden(true)
                            ->setMaxAttempts(10);

                        $question->setValidator(
                            function ($value) {
                                if (!trim($value)) {
                                    throw new Exception('The password can not be empty.');
                                }

                                return $value;
                            }
                        );

                        $password = null;

                        while (!$password) {
                            $password = trim($helper->ask($input, $output, $question));
                            $host->setSshPassword($password);
                        }

                        $host->getSshTunnel()->setBatchMode(false);
                    }
                }
            )
            ->addStep(
                'handle-running-inside-vagrant',
                function () {
                    if (VagrantExtension::isInsideVagrant()) {
                        $this->getProject()->getEnvironment('vagrant')->getHost('127.0.0.1')
                            ->setSshHomeFolder('/home/vagrant');
                    }
                }
            )
            ->addStep($this->getProject()->getScript('ssh:fix-key-permissions'))
            ->addStep(
                'ensure-expect-script-is-executable',
                function () {
                    $path = __DIR__ . '/../_files/ssh-with-password.exp';

                    if (!is_executable($path)) {
                        Exec::run(
                            sprintf('chmod +x %s', escapeshellarg($path)),
                            $output,
                            $exitStatus
                        );
                    }
                }
            )
            ->addStep(
                'check-for-public-key',
                function () use ($publicKey) {
                    if (!file_exists($publicKey)) {
                        throw new Exception('SSH keys have not been generated.  Run ssh:generate-keys.');
                    }
                }
            )
            ->addStep(
                'copy-public-key',
                $this->getProject()->scp($publicKey, '')
            )
            ->addStep(
                'create-ssh-folder',
                $this->getProject()->ssh('mkdir -p .ssh')
            )
            ->addStep(
                'allow-authorized-keys-writes',
                $this->getProject()->ssh('touch .ssh/authorized_keys && chmod +w .ssh/authorized_keys')
            )
            ->addStep(
                'label-key',
                $this->getProject()->ssh(
                    sprintf(
                        'echo %s >> .ssh/authorized_keys',
                        escapeshellarg(
                            sprintf('# Delta CLI key for %s', $this->getProject()->getName())
                        )
                    )
                )
            )
            ->addStep(
                'add-key',
                $this->getProject()->ssh('cat id_rsa.pub >> .ssh/authorized_keys')
            )
            ->addStep(
                'change-authorized-keys-permissions',
                $this->getProject()->ssh('chmod 400 .ssh/authorized_keys')
            )
            ->addStep(
                'change-ssh-folder-permissions',
                $this->getProject()->ssh('chmod 700 .ssh')
            )
            ->addStep($this->getProject()->logAndSendNotifications());
    }
}
