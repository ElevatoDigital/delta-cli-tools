<?php

namespace DeltaCli\Extension;

use DeltaCli\Cache;
use DeltaCli\Extension\Vagrant\Config;
use DeltaCli\Extension\Vagrant\Script\BackupDbs;
use DeltaCli\Extension\Vagrant\Script\CheckEnvironment;
use DeltaCli\Extension\Vagrant\Script\CreateVhost;
use DeltaCli\Extension\Vagrant\Script\Mysql\Create as CreateMysql;
use DeltaCli\Extension\Vagrant\Script\Postgres\Create as CreatePostgres;
use DeltaCli\Extension\Vagrant\Script\RestartServices;
use DeltaCli\Extension\Vagrant\Script\SetPath;
use DeltaCli\Extension\Vagrant\Script\SetSyncedDirPath;
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

        $project->addScript(new SetPath($project));

        if (file_exists($vagrantPrivateKeyPath) || self::isInsideVagrant()) {
            $this->addScripts($project);

            if (!$project->hasEnvironmentInternal('vagrant')) {
                $this->createEnvironment($project, $vagrantPrivateKeyPath, $cwd);
            }
        }
    }

    public static function isInsideVagrant()
    {
        return 'vagrant' === $_SERVER['USER'];
    }

    private function addScripts(Project $project)
    {
        $project->addScript(new BackupDbs($project, $this->cache));
        $project->addScript(new CheckEnvironment($project, $this->cache));
        $project->addScript(new RestartServices($project));
        $project->addScript(new CreateVhost($project, $this->cache));
        $project->addScript(new CreatePostgres($project));
        $project->addScript(new CreateMysql($project));
	$project->addScript(new SetPath($project));
	$project->addScript(new SetSyncedDirPath($project));
    }

    private function createEnvironment(Project $project, $vagrantPrivateKeyPath, $cwd)
    {
        if ($vagrantPrivateKeyPath) {
            $isInsideVagrant = false;
        } else {
            $isInsideVagrant = true;
        }

        $environment = $project->createEnvironment('vagrant')
            ->setUsername('vagrant')
            ->setApplicationEnv('development')
            ->setIsDevEnvironment(true)
            ->addHost('127.0.0.1');

        if ($isInsideVagrant) {
            $port = 22;
        } else {
            $port = 2222;

            $environment->setSshPrivateKey($vagrantPrivateKeyPath);
        }

        if ($this->cache->fetch('delta-synced-dir') === $cwd || 0 === strpos($cwd, $this->cache->fetch('delta-synced-dir'))) {
            $homeFolder = $cwd;
        } else {
            $homeFolder = $this->cache->fetch('delta-synced-dir');
        }

        $environment->getHost('127.0.0.1')
            ->setSshPort($port)
            ->setSshHomeFolder($homeFolder)
            ->setAdditionalSshOptions(
                [
                    'Compression'           => 'yes',
                    'DSAAuthentication'     => 'yes',
                    'LogLevel'              => 'FATAL',
                    'StrictHostKeyChecking' => 'no',
                    'UserKnownHostsFile'    => '/dev/null',
                    'IdentitiesOnly'        => 'yes'
                ]
            );

        $environment->setManualConfig(new Config($environment->getHost('127.0.0.1'), $this->cache));
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
