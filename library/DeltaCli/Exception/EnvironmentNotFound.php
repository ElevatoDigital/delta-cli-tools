<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Environment;
use DeltaCli\Host;
use Exception;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class EnvironmentNotFound extends Exception implements ConsoleOutputInterface
{
    private $name;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Environment[]
     */
    private $environments = [];

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    public function setEnvironments(array $environments)
    {
        $this->environments = $environments;

        return $this;
    }

    public function hasBanner()
    {
        return true;
    }

    public function outputToConsole(OutputInterface $output)
    {
        $banner = new Banner($output);
        $banner
            ->setBackground('red')
            ->render("Environment with name '{$this->name}' not found.");

        $output->writeln(
            [
                "We could not find an environment with the name '{$this->name}'.  You can add environments",
                'in your delta-cli.php file using $project->createEnvironment().',
                ''
            ]
        );

        $output->writeln(
            [
                'Here are the environments currently available in your project:'
            ]
        );

        $table = new Table($this->output);

        $table->setHeaders(['Name', 'Host(s)', 'Is Dev Environment?']);
        $table->setRows($this->getEnvironmentTableRows($this->environments));
        $table->render();
    }

    private function getEnvironmentTableRows($environments)
    {
        $rows = [];

        /* @var $environment Environment */
        foreach ($environments as $environment) {
            $hosts = [];

            /* @var $host Host */
            foreach ($environment->getHosts() as $host) {
                $hosts[] = $host->getHostname();
            }

            $rows[] = [
                $environment->getName(),
                implode(PHP_EOL, $hosts),
                $environment->isDevEnvironment() ? 'Yes' : 'No'
            ];
        }

        return $rows;
    }
}
