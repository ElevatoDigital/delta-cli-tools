<?php

namespace DeltaCli\Extension;

use DeltaCli\Cache;
use DeltaCli\Extension\Vagrant\Script\BackupDbs;
use DeltaCli\Extension\Vagrant\Script\CheckEnvironment;
use DeltaCli\Extension\Vagrant\Script\CreateVhost;
use DeltaCli\Extension\Vagrant\Script\Mysql\Create as CreateMysql;
use DeltaCli\Extension\Vagrant\Script\Postgres\Create as CreatePostgres;
use DeltaCli\Extension\Vagrant\Script\RestartServices;
use DeltaCli\Project;
use DeltaCli\VagrantFinder;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Vagrant implements ExtensionInterface
{
    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(Cache $cache, QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output)
    {
        $this->cache          = $cache;
        $this->questionHelper = $questionHelper;
        $this->input          = $input;
        $this->output         = $output;
    }

    public function extend(Project $project)
    {
        $cwd = getcwd();

        $vagrantPath = $this->findVagrantPath();

        $vagrantPrivateKeyPath = null;

        if ($vagrantPath) {
            $vagrantPrivateKeyPath = $this->findVagrantPath() . '/.vagrant/machines/default/virtualbox/private_key';
        }

        if (!$project->hasEnvironment('vagrant') && file_exists($vagrantPrivateKeyPath)) {
            $project->addScript(new BackupDbs($project));
            $project->addScript(new CheckEnvironment($project));
            $project->addScript(new RestartServices($project));
            $project->addScript(new CreateVhost($project));
            $project->addScript(new CreatePostgres($project));
            $project->addScript(new CreateMysql($project));

            $project->createEnvironment('vagrant')
                ->setUsername('vagrant')
                ->setSshPrivateKey($vagrantPrivateKeyPath)
                ->setApplicationEnv('development')
                ->setIsDevEnvironment(true)
                ->addHost('127.0.0.1');

            if ('/delta' === $cwd || 0 === strpos($cwd, '/delta/')) {
                $homeFolder = $cwd;
            } else {
                $homeFolder = '/delta';
            }

            $project->getEnvironment('vagrant')->getHost('127.0.0.1')
                ->setSshPort(2222)
                ->setSshHomeFolder($homeFolder)
                ->setAdditionalSshOptions(
                    [
                        'Compression' => 'yes',
                        'DSAAuthentication' => 'yes',
                        'LogLevel' => 'FATAL',
                        'StrictHostKeyChecking' => 'no',
                        'UserKnownHostsFile' => '/dev/null',
                        'IdentitiesOnly' => 'yes'
                    ]
                );
        }
    }

    private function findVagrantPath()
    {
        if ((!$vagrantPath = $this->cache->fetch('vagrant-path'))) {
            $finder = new VagrantFinder($this->questionHelper, $this->input, $this->output);

            $vagrantPath = $finder->locateVagrantPath();

            if ($vagrantPath) {
                $this->cache->store('vagrant-path', $vagrantPath);
            }
        }

        return $vagrantPath;
    }
}
