<?php

namespace DeltaCli\Log;

use DeltaCli\Config\Database\DatabaseInterface;
use DeltaCli\Host;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SticklerLog implements LogInterface
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

    public function getHost()
    {
        return $this->host;
    }

    public function getName()
    {
        return 'stickler-log';
    }

    public function getDescription()
    {
        return "delta_log table of the {$this->database->getDatabaseName()} database.";
    }

    public function getWatchByDefault()
    {
        return true;
    }

    public function attachToEventLoop(LoopInterface $loop, OutputInterface $output)
    {
        $deltaLogId = $this->handleMessages($output);

        $this->timer = $loop->addPeriodicTimer(
            5,
            function () use ($output, &$deltaLogId) {
                $deltaLogId = $this->handleMessages($output, $deltaLogId);
            }
        );
    }

    public function stop()
    {
       if ($this->timer) {
           $this->timer->cancel();
       }
    }

    private function handleMessages(OutputInterface $output, $afterDeltaLogId = null)
    {
        $messages   = $this->fetchMessages($afterDeltaLogId);
        $deltaLogId = $this->getHighestDeltaLogIdFromMessages($messages, $afterDeltaLogId);

        $this->displayMessages($messages, $output);

        return $deltaLogId;
    }

    private function getHighestDeltaLogIdFromMessages(array $messages, $deltaLogId)
    {
        foreach ($messages as $message) {
            if ($message['delta_log_id'] > $deltaLogId) {
                $deltaLogId = $message['delta_log_id'];
            }
        }

        return $deltaLogId;
    }

    private function fetchMessages($afterDeltaLogId = null)
    {
        $whereClause = '';
        $limitClause = '';

        $params = [];

        if (null === $afterDeltaLogId) {
            $limitClause = 'LIMIT 10';
        } else {
            $whereClause = 'WHERE delta_log_id > %s';
            $params[]    = $afterDeltaLogId;
        }

        $sql = "SELECT delta_log_id, REPLACE(message, E'\n', ' ') AS message, date_created 
            FROM delta_log
            {$whereClause}
            ORDER BY delta_log_id DESC
            {$limitClause}";

        $messages = $this->database->query($sql, $params);

        rsort($messages);

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