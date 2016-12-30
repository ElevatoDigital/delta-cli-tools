<?php

namespace DeltaCli\Script;

use DeltaCli\Config\Database;
use DeltaCli\Host;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Helper\Table;

class DatabaseTunnel extends Script
{
    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'db:tunnel',
            'Create an SSH tunnel to connect to a database in a GUI.'
        );
    }

    protected function configure()
    {
        $this->requireEnvironment();
        parent::configure();
    }

    protected function addSteps()
    {
        $findDbsStep = $this->getProject()->findDatabases();

        $this
            ->addStep($findDbsStep)
            ->addStep(
                'open-tunnel',
                function () use ($findDbsStep) {
                    /* @var Host $tunnelHost */
                    /* @var Database $database */
                    $environment = $this->getProject()->getSelectedEnvironment();
                    $tunnelHost  = reset($environment->getHosts());
                    $database    = reset($findDbsStep->getDatabases());
                    $dbHost      = new Host($this->getDbHostname($database, $tunnelHost), $environment);

                    $tunnelHost->getSshTunnel()->tunnelConnectionsForHost($dbHost, $dbHost->getUsername());
                    $tunnelHost->getSshTunnel()->setRemotePort(5432);
                    $port = $tunnelHost->getSshTunnel()->setUp();

                    $output = $this->getProject()->getOutput();

                    $output->writeln(
                        [
                            'You can now configure you database application to connect to the tunnel using',
                            'the following information.',
                            ''
                        ]
                    );

                    $table = new Table($output);

                    $table->addRows(
                        [
                            ['Host', 'localhost'],
                            ['Port', $port],
                            ['Username', $database->getUsername()],
                            ['Password', $database->getPassword()],
                            ['DB Name', $database->getDatabaseName()]
                        ]
                    );

                    $table->render();

                    $output->writeln(
                        [
                            '',
                            '<comment>Press enter to close the tunnel when you are ready to disconnect...</comment>'
                        ]
                    );

                    fgetc(STDIN);

                    $tunnelHost->getSshTunnel()->tearDown();
                }
            );
    }

    private function getDbHostname(Database $database, Host $tunnelHost)
    {
        if ($database->getHost() === 'localhost') {
            return $tunnelHost->getHostname();
        } else {
            return $database->getHost();
        }
    }
}
