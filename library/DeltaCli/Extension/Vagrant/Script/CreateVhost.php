<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Extension\Vagrant\Exception\VirtualHostConfigurationAlreadyExists;
use DeltaCli\FileTemplate;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateVhost extends Script
{
    private $path;

    private $hostname;

    private $documentRoot;

    private $applicationEnv = 'development';

    public function __construct(Project $project, $path = null)
    {
        parent::__construct(
            $project,
            'vagrant:create-vhost',
            'Create virtual host configuration files for Apache and nginx.'
        );

        if (null === $path) {
            $this->path = '/delta/vhost.d';
        } else {
            $this->path = rtrim($path, '/');
        }
    }

    public function setHostname($hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = $documentRoot;

        return $this;
    }

    public function setApplicationEnv($applicationEnv)
    {
        $this->applicationEnv = $applicationEnv;

        return $this;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addSetterArgument('hostname', InputArgument::REQUIRED, 'The host/domain name for this virtual host.')
            ->addSetterArgument('document-root', InputArgument::REQUIRED, 'The root folder to server for this host.')
            ->addSetterOption('application-env', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apachePath = $this->path . '/' . $this->hostname . '.conf';
        $nginxPath  = $this->path . '/nginx/' . $this->hostname . '.conf';

        $this
            ->addStep($this->getProject()->getScript('vagrant:check-environment'))
            ->addStep(
                'check-apache-conf-already-exists',
                function () use ($apachePath) {
                    if (file_exists($apachePath)) {
                        throw new VirtualHostConfigurationAlreadyExists(
                            "Apache virtual host configuration already exists at {$apachePath}."
                        );
                    }
                }
            )
            ->addStep(
                'check-nginx-conf-already-exists',
                function () use ($nginxPath) {
                    if (file_exists($nginxPath)) {
                        throw new VirtualHostConfigurationAlreadyExists(
                            "nginx virtual host configuration already exists at {$nginxPath}."
                        );
                    }
                }
            )
            ->addStep(
                'create-apache-conf',
                function () use ($apachePath) {
                    $template = new FileTemplate(__DIR__ . '/_files/apache.conf.php');
                    $template
                        ->assign('hostname', $this->hostname)
                        ->assign('docRoot', $this->documentRoot)
                        ->assign('applicationEnv', $this->applicationEnv);
                    $template->write($apachePath);
                }
            )
            ->addStep(
                'create-nginx-conf',
                function () use ($nginxPath) {
                    $template = new FileTemplate(__DIR__ . '/_files/nginx.conf.php');
                    $template
                        ->assign('hostname', $this->hostname)
                        ->assign('docRoot', $this->documentRoot);
                    $template->write($nginxPath);
                }
            )
            ->addStep($this->getProject()->getScript('vagrant:restart-services'))
            ->addStep(
                'display-hosts-file-help',
                function () use ($output) {
                    $banner = new Banner($output);
                    $banner->setBackground('cyan');
                    $banner->render("Add {$this->hostname} to your host operating system's hosts file.");

                    $output->writeln(
                        [
                            'Learn more about adding entries to your hosts file or using dnsmasq on Github at:',
                            '<fg=blue;options=underscore>http://bit.ly/delta-cli-hosts-file</>'
                        ]
                    );
                }
            );

        return parent::execute($input, $output);
    }
}
