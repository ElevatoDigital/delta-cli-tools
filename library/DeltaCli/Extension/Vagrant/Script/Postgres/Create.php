<?php

namespace DeltaCli\Extension\Vagrant\Script\Postgres;

use DeltaCli\Extension\Vagrant\Exception;
use DeltaCli\Project;
use DeltaCli\Script;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Script
{
    private $databaseName;

    private $username;

    private $password;

    public function __construct(Project $project)
    {
        parent::__construct(
            $project,
            'vagrant:create-postgres',
            'Create a Postgres database.'
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->username) {
            $this->setUsername($this->databaseName);
        }

        if (!$this->password) {
            $this->setPassword($this->password);
        }

        $this
            ->addStep($this->getProject()->getScript('vagrant:check-environment'))
            // See http://stackoverflow.com/questions/14549270/check-if-database-exists-in-postgresql-using-shell
            ->addStep(
                'check-if-database-already-exists',
                function () {
                    exec(
                        sprintf(
                            'psql -lqt -U postgres | cut -d \| -f 1 | grep -qw %s',
                            escapeshellarg($this->databaseName)
                        ),
                        $output,
                        $exitStatus
                    );

                    if (!$exitStatus) {
                        throw new Exception("{$this->databaseName} already exists.");
                    }
                }
            )
            ->addStep('create-user', $this->assembleCreateUserCommand())
            ->addStep('create-database', $this->assembleCreateDatabaseCommand())
            ->addStep('drop-public-schema', $this->assembleDropPublicSchemaCommand())
            ->addStep('create-public-schema', $this->assembleCreatePublicSchemaCommand());

        return parent::execute($input, $output);
    }

    private function assembleCreateUserCommand()
    {
        return $this->sendSqlToPostgres(
            sprintf(
                "CREATE USER %s WITH PASSWORD '%s';",
                $this->username,
                $this->password
            ),
            'postgres'
        );
    }

    private function assembleCreateDatabaseCommand()
    {
        return $this->sendSqlToPostgres(
            sprintf(
                'CREATE DATABASE %s OWNER %s;',
                $this->databaseName,
                $this->username
            ),
            'postgres'
        );
    }

    private function assembleDropPublicSchemaCommand()
    {
        return $this->sendSqlToPostgres('DROP SCHEMA public CASCADE;', 'postgres', $this->databaseName);
    }

    private function assembleCreatePublicSchemaCommand()
    {
        return $this->sendSqlToPostgres('CREATE SCHEMA public;', $this->username, $this->databaseName);
    }

    private function sendSqlToPostgres($sql, $username, $database = null)
    {
        return sprintf(
            'echo %s | psql -U %s %s',
            escapeshellarg($sql),
            escapeshellarg($username),
            ($database ? escapeshellarg($database) : '')
        );
    }
}
