<?php

namespace DeltaCli\Command;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Environment;
use DeltaCli\Host;
use DeltaCli\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListEnvironments extends Command
{
    /**
     * @var Project
     */
    private $project;

    public function __construct(Project $project)
    {
        parent::__construct(null);

        $this->project = $project;
    }

    protected function configure()
    {
        $this
            ->setName('list-environments')
            ->setDescription('List the environments available on this project.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environments = $this->project->getEnvironments();

        if (0 === count($environments)) {
            $this->displayNoEnvironmentsBanner($output);
        } else {
            $table = new Table($output);
            $table->setHeaders(['Name', 'Host(s)']);
            $table->setRows($this->getEnvironmentTableRows($environments));
            $table->render();
        }
    }

    private function displayNoEnvironmentsBanner(OutputInterface $output)
    {
        $banner = new Banner($output);
        $banner->setBackground('cyan');
        $banner->render('No environments have been added to this project.');

        $output->writeln(
            [
                'Learn more about how to add environments to your delta-cli.php on Github at:',
                '<fg=blue;options=underscore>https://github.com/DeltaSystems/delta-cli-tools</>'
            ]
        );
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
                implode(PHP_EOL, $hosts)
            ];
        }

        return $rows;
    }
}
