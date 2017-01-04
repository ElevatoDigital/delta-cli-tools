<?php

namespace DeltaCli\Extension\Vagrant\Script\Mysql;

use DeltaCli\Exec;
use DeltaCli\Extension\Vagrant\Exception;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Create extends Script
{
    private $databaseName;

    private $username;

    private $password;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:create-mysql',
            'Create a MySQL database.'
        );
    }

    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;

        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addSetterArgument('database-name', InputArgument::REQUIRED, 'The name of the database.')
            ->addSetterOption('username', null, InputOption::VALUE_REQUIRED)
            ->addSetterOption('password', null, InputOption::VALUE_REQUIRED);
    }

    protected function addSteps()
    {
        if (!$this->username) {
            $this->setUsername(substr($this->databaseName, 0, 16));
        }

        if (!$this->password) {
            $this->setPassword($this->databaseName);
        }

        $this
            ->addStep($this->getProject()->getScript('vagrant:check-environment'))
            ->addStep(
                'check-if-database-already-exists',
                function () {
                    $host   = $this->getEnvironment()->getHost('127.0.0.1');
                    $tunnel = $host->getSshTunnel();

                    $tunnel->setUp();

                    Exec::run(
                        $tunnel->assembleSshCommand(
                            sprintf(
                                'echo \'SHOW DATABASES;\' | mysql --user=root --password=delta | grep -v ^Database |'
                                . 'grep -qw %s',
                                escapeshellarg($this->databaseName)
                            )
                        ),
                        $output,
                        $exitStatus
                    );

                    $tunnel->tearDown();

                    if (!$exitStatus) {
                        throw new Exception("{$this->databaseName} already exists.");
                    }
                }
            )
            ->addStep('create-database', $this->getProject()->ssh($this->assembleCreateDatabaseCommand()))
            ->addStep('grant-privileges', $this->getProject()->ssh($this->assembleGrantPrivilegesCommand()));
    }

    protected function preRun()
    {
        $this->setEnvironment('vagrant');
    }

    private function assembleCreateDatabaseCommand()
    {
        return $this->sendSqlToMysql(
            sprintf(
                'CREATE DATABASE %s;',
                $this->databaseName
            )
        );
    }

    private function assembleGrantPrivilegesCommand()
    {
        return $this->sendSqlToMysql(
            sprintf(
                "GRANT ALL PRIVILEGES ON %s.* TO '%s'@'localhost' IDENTIFIED BY '%s';",
                $this->databaseName,
                $this->username,
                $this->password
            )
        );
    }

    private function sendSqlToMysql($sql)
    {
        return sprintf(
            'echo %s | mysql --user=root --password=delta',
            escapeshellarg($sql)
        );
    }
}
