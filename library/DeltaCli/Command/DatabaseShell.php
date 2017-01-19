<?php

namespace DeltaCli\Command;

use DeltaCli\Command;
use DeltaCli\Debug;
use DeltaCli\Environment;
use DeltaCli\Project;
use DeltaCli\Script;
use DeltaCli\Script\Step\FindDatabases;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseShell extends Command
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var FindDatabases
     */
    private $findDatabasesStep;

    public function __construct(Project $project)
    {
        $this->project = $project;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this
            ->setName('db:shell')
            ->setDescription('Open a database command-line shell.')
            ->addArgument('environment', InputArgument::REQUIRED, 'The environment where you want to open a shell.')
            ->addOption('hostname', null, InputOption::VALUE_REQUIRED, 'The specific hostname you want to connect to.');

        $this->findDatabasesStep = $this->project->findDatabases()
            ->configure($this->getDefinition());
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $this->project->getSelectedEnvironment();

        if ($input->getOption('hostname')) {
            $host = $env->getSelectedHost($input->getOption('hostname'));
        } else {
            $hosts = $env->getHosts();
            $host  = reset($hosts);
        }

        $this->project->findDatabases()
            ->setSelectedEnvironment($env);

        $script = $this->generateScript($env, $this->findDatabasesStep, $input);
        $script->run(new ArrayInput([]), $output);

        $database = $this->findDatabasesStep->getSelectedDatabase($input);
        $tunnel   = $host->getSshTunnel();

        $database->renderShellHelp($output);

        $command = $tunnel->assembleSshCommand($database->getShellCommand(), '-t');
        Debug::log("Opening DB shell with `{$command}`...");
        passthru($command);

        $tunnel->tearDown();
    }

    private function generateScript(Environment $env, FindDatabases $findDatabasesStep, InputInterface $input)
    {
        $script = new Script(
            $this->project,
            'open-db-shell',
            'Script that runs prior to opening DB shell and sends notifications.'
        );

        $script->setApplication($this->getApplication());

        $script
            ->setEnvironment($env)
            ->addStep($this->project->logAndSendNotifications())
            ->addStep($findDatabasesStep)
            ->addStep(
                'open-db-shell',
                function () use ($findDatabasesStep, $env, $input) {
                    $database  = $findDatabasesStep->getSelectedDatabase($input);
                    echo "Opening database shell for '{$database->getDatabaseName()}' on {$env->getName()}." . PHP_EOL;
                }
            );

        return $script;
    }
}
