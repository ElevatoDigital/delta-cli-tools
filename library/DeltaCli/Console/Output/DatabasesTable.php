<?php

namespace DeltaCli\Console\Output;

use DeltaCli\Config\Database\DatabaseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class DatabasesTable
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var DatabaseInterface[]
     */
    private $databases;

    public function __construct(OutputInterface $output, array $databases)
    {
        $this->output    = $output;
        $this->databases = $databases;
    }

    public function render()
    {
        $table = new Table($this->output);

        $table->setHeaders(['DB Name', 'Host', 'Username', 'Password', 'Type']);

        foreach ($this->databases as $database) {
            $table->addRow(
                [
                    $database->getDatabaseName(),
                    $database->getHost(),
                    $database->getUsername(),
                    $database->getPassword(),
                    $database->getType()
                ]
            );
        }

        $table->render();
    }
}