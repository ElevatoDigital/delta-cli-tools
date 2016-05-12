<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class RequiredVersionNotInstalled extends Exception implements ConsoleOutputInterface
{
    /**
     * @var string
     */
    private $requiredVersion;

    public function setRequiredVersion($requiredVersion)
    {
        $this->requiredVersion = $requiredVersion;

        return $this;
    }

    public function outputToConsole(OutputInterface $output)
    {
        $banner = new Banner($output);
        $banner->render('Required Delta CLI version not installed.');

        $output->writeln(
            [
                "Version {$this->requiredVersion} is required for this project.",
                '',
                'Update your Delta CLI Tools by running:',
                'composer global install'
            ]
        );
    }
}
