<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Cache;
use DeltaCli\Console\Output\Banner;
use DeltaCli\Extension\Vagrant\Exception\VirtualHostConfigurationAlreadyExists;
use DeltaCli\Extension\Vagrant\Exception\VirtualHostConfigurationCannotBeSaved;
use DeltaCli\FileTemplate;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateVhost extends Script
{
    private $cache;

    private $path;

    private $hostname;

    private $documentRoot;

    private $applicationEnv = 'development';

    public function __construct(Project $project, Cache $cache, $path = null)
    {
        parent::__construct(
            $project,
            'vagrant:create-vhost',
            'Create virtual host configuration files for Apache and nginx.'
        );

        $this->cache = $cache;

        if (null === $path) {
            $this->path = $this->cache->fetch('synced-dir-path') . '/vhost.d';
        } else {
            $this->path = rtrim($path, '/');
        }
    }

    public function setHostname($hostname)
    {
        if (false === strpos($hostname, '.')) {
            $hostname .= '.local';
        }

        $this->hostname = $hostname;

        return $this;
    }

    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = realpath($documentRoot);

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
            ->addSetterArgument(
                'hostname',
                InputArgument::REQUIRED,
                'The host/domain name for this virtual host.  (e.g. my-project.local)'
            )
            ->addSetterArgument(
                'document-root',
                InputArgument::REQUIRED,
                'The root folder to serve for this host. (Typically a www or public folder.)'
            )
            ->addSetterOption('application-env', null, InputOption::VALUE_OPTIONAL);
    }

    protected function addSteps()
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

                    if (!file_exists($apachePath)) {
                        throw new VirtualHostConfigurationCannotBeSaved(
                            "delta-cli was unable to save virtual host configuration at {$apachePath}. Check your privilidges?"
                        );
                    }
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

                    if (!file_exists($nginxPath)) {
                        throw new VirtualHostConfigurationCannotBeSaved(
                            "delta-cli was unable to save virtual host configuration at {$nginxPath}. Check your privilidges?"
                        );
                    }
                }
            )
            ->addStep(
                $this->getProject()->getScript('vagrant:restart-services')
                    ->setEnvironment('vagrant')
            )
            ->addStep(
                'display-hosts-file-help',
                function () {
                    $output = $this->getProject()->getOutput();

                    $banner = new Banner($output);
                    $banner->setBackground('cyan');
                    $banner->render("Add {$this->hostname} to your host operating system's hosts file.");

                    $output->writeln(
                        [
                            'On OS X or Linux, you can run the following command:',
                            "sudo bash -c \"echo '127.0.0.1 {$this->hostname}' >> /etc/hosts\"",
                            ''
                        ]
                    );

                    $output->writeln(
                        [
                            'Learn more about adding entries to your hosts file or using dnsmasq on Github at:',
                            '<fg=blue;options=underscore>http://bit.ly/delta-cli-hosts-file</>'
                        ]
                    );
                }
            );
    }
}
