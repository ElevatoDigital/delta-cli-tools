<?php

namespace DeltaCli\Script;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Helper\Table;

class DatabaseTunnel extends Script
{
    /**
     * @var Script\Step\FindDatabases
     */
    private $findDbsStep;

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

        $this->findDbsStep = $this->getProject()->findDatabases();
        $this->findDbsStep->configure($this->getDefinition());

        parent::configure();
    }

    protected function addSteps()
    {

        $this
            ->addStep($this->findDbsStep)
            ->addStep(
                'open-tunnel',
                function () {
                    /* @var Host $tunnelHost */
                    /* @var DatabaseInterface $database */
                    $environment = $this->getProject()->getSelectedEnvironment();

                    $environmentHosts = $environment->getHosts();
                    $tunnelHost       = reset($environmentHosts);

                    $database    = $this->findDbsStep->getSelectedDatabase($this->getProject()->getInput());
                    $dbHost      = new Host($this->getDbHostname($database, $tunnelHost), $environment);

                    $tunnelHost->getSshTunnel()->tunnelConnectionsForHost($dbHost, $dbHost->getUsername());
                    $tunnelHost->getSshTunnel()->setRemotePort($database->getPort());
                    $port = $tunnelHost->getSshTunnel()->setUp();

                    $output = $this->getProject()->getOutput();

                    $output->writeln(
                        [
                            'You can now configure your database application to connect to the tunnel using',
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

    private function getDbHostname(DatabaseInterface $database, Host $tunnelHost)
    {
        if ($database->getHost() === 'localhost') {
            return $tunnelHost->getHostname();
        } else {
            return $database->getHost();
        }
    }
}
