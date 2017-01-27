<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Environment;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class NoDatabasesAvailable extends Exception implements ConsoleOutputInterface
{
    /**
     * @var Environment
     */
    private $environment;

    public function setEnvironment($environment)
    {
        $this->environment = $environment;

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
            ->render("No databases available in the '{$this->environment->getName()}' environment.");

        $output->writeln(
            [
                'There are no databases available in the selected environment.  Delta CLI will detect databases',
                'in WordPress, Zend Framework 1, TriPoint Hosting and Dewdrop configuration files.',
                'If you need to manually add a database not present in one of those types of configuration files,',
                'you can do so in your delta-cli.php like so:',
                '',
                '    use DeltaCli\Config\Database\DatabaseFactory as Db;',
                '',
                '    $project->getEnvironment(\'' . $this->environment->getName() . '\')->getManualConfig()',
                '        ->addDatabase(Db::createInstance(\'mysql\', \'dbname\', \'username\', \'password\'));'
            ]
        );
    }
}
