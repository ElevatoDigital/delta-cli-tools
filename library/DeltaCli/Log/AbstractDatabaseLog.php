<?php

namespace DeltaCli\Log;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDatabaseLog implements LogInterface
{
    /**
     * @var Host
     */
    private $host;

    /**
     * @var DatabaseInterface
     */
    private $database;

    /**
     * @var TimerInterface
     */
    private $timer;

    public function __construct(Host $host, DatabaseInterface $database)
    {
        $this->host     = $host;
        $this->database = $database;
    }

    abstract public function assembleSql($afterId = null);

    public function getHost()
    {
        return $this->host;
    }

    abstract public function getName();

    abstract public function getDescription();

    public function getWatchByDefault()
    {
        return true;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function attachToEventLoop(LoopInterface $loop, OutputInterface $output)
    {
        $deltaLogId = $this->handleMessages($output);

        $this->host->getSshTunnel()->setUp();

        $this->timer = $loop->addPeriodicTimer(
            5,
            function () use ($output, &$deltaLogId) {
                $deltaLogId = $this->handleMessages($output, $deltaLogId);
            }
        );
    }

    public function stop()
    {
        $this->host->getSshTunnel()->tearDown();
        
        if ($this->timer) {
            $this->timer->cancel();
        }
    }

    private function handleMessages(OutputInterface $output, $afterId = null)
    {
        $messages = $this->fetchMessages($afterId);
        $afterId  = $this->getHighestIdFromMessages($messages, $afterId);

        $this->displayMessages($messages, $output);

        return $afterId;
    }

    private function getHighestIdFromMessages(array $messages, $afterId)
    {
        foreach ($messages as $message) {
            if ($message['id'] > $afterId) {
                $afterId = $message['id'];
            }
        }

        return $afterId;
    }

    private function fetchMessages($afterId = null)
    {
        $sqlAndParams = $this->assembleSql($afterId);
        $messages     = $this->database->query($sqlAndParams['sql'], $sqlAndParams['params']);

        return $messages;
    }

    private function displayMessages(array $messages, OutputInterface $output)
    {
        foreach ($messages as $message) {
            $output->writeln($this->assembleOutputLine($message));
        }
    }

    private function assembleOutputLine(array $messageData)
    {
        $logInfo = sprintf(
            '<fg=blue>%s <%s></>',
            $this->getName(),
            $this->host->getHostname()
        );

        $message = sprintf(
            '[%s] %s',
            $messageData['date_created'],
            $messageData['message']
        );

        return [$logInfo, trim($message)];
    }
}