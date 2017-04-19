<?php

namespace DeltaCli\Environment;

use DeltaCli\Config\Config;
use DeltaCli\Config\ConfigFactory;
use DeltaCli\Console\Output\DatabasesTable;
use DeltaCli\Environment;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class ResourceRenderer
{
    private $environment;

    private $output;

    public function __construct(Environment $environment, OutputInterface $output)
    {
        $this->environment = $environment;
        $this->output      = $output;
    }

    public function render()
    {
        $this->output->writeln("<comment>Environment Information</comment>");

        $configs = $this->loadConfigs();

        $table = new Table($this->output);

        $table
            ->addRow(['Name', $this->environment->getName()])
            ->addRow(['Is Dev Environment?', ($this->environment->isDevEnvironment() ? 'Yes' : 'No')])
            ->addRow(['Host(s)', $this->renderHostNames()])
            ->addRow(['SSH Username', $this->environment->getUsername()]);

        if ($this->environment instanceof ApiEnvironment) {
            $table->addRow(['SSH Password', $this->environment->getPassword()]);
        }

        $table->addRow(['Browser URL', $this->renderBrowserUrl($configs)]);

        $table->render();

        $this->output->writeln(['', '<comment>Databases</comment>']);

        $databases = $this->getDatabasesFromConfigs($configs);

        if (!count($databases)) {
            $this->output->writeln('No databases were found in this environment.');
        } else {
            $table = new DatabasesTable($this->output, $databases);
            $table->render();
        }
    }

    private function loadConfigs()
    {
        $hosts = $this->environment->getHosts();
        $host  = reset($hosts);

        return (new ConfigFactory)->detectConfigsOnHost($host);
    }

    private function renderHostNames()
    {
        $hostnames = [];

        foreach ($this->environment->getHosts() as $host) {
            $hostnames[] = $host->getHostname();
        }

        return implode(PHP_EOL, $hostnames);
    }

    /**
     * @param Config[] $configs
     */
    private function renderBrowserUrl(array $configs)
    {
        foreach ($configs as $config) {
            if ($config->hasBrowserUrl()) {
                return $config->getBrowserUrl();
            }
        }

        return '&lt;unknown&gt;';
    }

    /**
     * @param Config[] $configs
     */
    private function getDatabasesFromConfigs(array $configs)
    {
        $databases = [];

        foreach ($configs as $config) {
            foreach ($config->getDatabases() as $database) {
                $databases[] = $database;
            }
        }

        return $databases;
    }
}