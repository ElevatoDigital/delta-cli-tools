<?php

namespace DeltaCli\Environment;

use DeltaCli\Config\Config;
use DeltaCli\Config\ConfigFactory;
use DeltaCli\Console\Output\DatabasesTable;
use DeltaCli\Environment;
use Symfony\Component\Console\Output\OutputInterface;

class NotebookHtmlRenderer
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
        $this->output->writeln("<h1>Basic Info</h1>");

        $configs = $this->loadConfigs();

        $this->output->writeln('<table><tbody>');
        $this->output->writeln("<tr><td>Environment Name</td><td>{$this->environment->getName()}</td></tr>");

        if ($this->environment->isDevEnvironment()) {
            $this->output->writeln("<tr><td>Is Dev Environment?</td><td>Yes</td></tr>");
        } else {
            $this->output->writeln("<tr><td>Is Dev Environment?</td><td>No</td></tr>");
        }

        $this->output->writeln("<tr><td>Host(s)?</td><td>{$this->renderHostNames()}</td></tr>");
        $this->output->writeln("<tr><td>SSH Username</td><td>{$this->environment->getUsername()}</td></tr>");

        if ($this->environment instanceof ApiEnvironment) {
            $this->output->writeln("<tr><td>SSH Password</td><td>{$this->environment->getPassword()}</td></tr>");
        }

        $this->output->writeln("<tr><td>Browser URL</td><td>{$this->renderBrowserUrl($configs)}</td></tr>");
        $this->output->writeln('</tbody></table>');

        $this->output->writeln('<h1>Databases</h1>');

        $databases = $this->getDatabasesFromConfigs($configs);

        if (!count($databases)) {
            $this->output->writeln('No databases are available in this environment.');
        } else {
            $table = new DatabasesTable($this->output, $databases);
            $table->renderNotebookHtml();
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