<?php

namespace DeltaCli\Exception;

use DeltaCli\Console\Output\Banner;
use DeltaCli\Environment;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class AttemptedPotentiallyDestructiveOperation extends Exception implements ConsoleOutputInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $authorizationCode;

    /**
     * @var string
     */
    private $operationDescription;

    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function setAuthorizationCode($authorizationCode)
    {
        $this->authorizationCode = $authorizationCode;

        return $this;
    }

    public function setOperationDescription($operationDescription)
    {
        $this->operationDescription = $operationDescription;

        return $this;
    }

    public function outputToConsole(OutputInterface $output)
    {
        $banner = new Banner($output);
        $banner
            ->setBackground('red')
            ->render("Attempting a potentially destructive operation on {$this->environment->getName()}.");

        $output->writeln(
            [
                'Attempted operation:',
                '  ' . $this->operationDescription,
                '',
                'Re-run your command with the following authorization code to continue:',
                '  ' . $this->authorizationCode,
                '',
                'Example command with authorization code:',
                "delta my-command {$this->environment->getName()} --authorization-code={$this->authorizationCode}",
                '',
                'If this environment is a dev environment and you do not need these sanity checks when performing',
                'these operations, you can call setIsDevEnvironment(true) on the environment in your delta-cli.php',
                'file.'
            ]
        );
    }

    public function hasBanner()
    {
        return true;
    }

}
