<?php

namespace DeltaCli\Extension\Vagrant\Script;

use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateVhost extends Script
{
    private $hostname;

    private $documentRoot;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:create-vhost',
            'Create virtual host configuration files for Apache and nginx.'
        );
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

    protected function configure()
    {
        parent::configure();

        $this
            ->addSetterOption('hostname', null, InputOption::VALUE_REQUIRED)
            ->addSetterOption('document-root', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->addStep(
                'check-apache-conf-already-exists',
                function () {

                }
            )
            ->addStep(
                'check-nginx-conf-already-exists',
                function () {

                }
            )
            ->addStep(
                'create-apache-conf',
                function () {

                }
            )
            ->addStep(
                'create-nginx-conf',
                function () {

                }
            )
            ->addStep($this->getProject()->getScript('vagrant:restart-services'))
            ->addStep(
                'display-hosts-file-help',
                function () {

                }
            );

        return parent::execute($input, $output);
    }
}
