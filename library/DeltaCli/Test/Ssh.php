<?php

namespace DeltaCli\Test;

use DeltaCli\Environment;
use DeltaCli\Project;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class Ssh extends PHPUnit_Framework_TestCase
{
    /**
     * @var Project
     */
    private $project;

    public function setUp()
    {
        if (!$this->isTestSshdRunning()) {
            $this->markTestSkipped(
                'Test skipped because the sshd daemon used for testing is not running.'
                . 'See http://bit.ly/delta-cli-ssh-testing'
            );
        }

        $input  = new ArgvInput();
        $output = new BufferedOutput();

        $this->project = new Project($input, $output);
    }

    protected function getTestEnvironment()
    {
        $environment = new Environment($this->project, 'test');
        $environment
            ->addHost('localhost')
            ->setUsername(SSHD_TEST_USERNAME)
            ->setSshPrivateKey(__DIR__ . '/_files/id_rsa');

        $environment->getHost('localhost')
            ->setSshPort(22222)
            ->setSshHomeFolder('/tmp/');

        return $environment;
    }

    private function isTestSshdRunning()
    {
        return $this->canConnectToSshd();
    }

    private function canConnectToSshd()
    {
        $connection = @stream_socket_client("tcp://localhost:22222", $errorNumber, $errorString);

        if ($errorString) {
            return false;
        }

        fclose($connection);

        return true;
    }
}
