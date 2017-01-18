<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Log\DatabaseManager;

class DetectorSet
{
    /**
     * @var DetectorInterface[]
     */
    private $detectors = [];

    public function __construct(DatabaseManager $databaseManager = null)
    {
        if (null === $databaseManager) {
            $databaseManager = new DatabaseManager();
        }

        $this->detectors[] = new ApacheErrorLog();
        $this->detectors[] = new ApacheAccessLog();
        $this->detectors[] = new DewdropMonolog();
        $this->detectors[] = new NginxAccessLog();
        $this->detectors[] = new NginxErrorLog();
        $this->detectors[] = new VagrantDewdropMonolog();
        $this->detectors[] = new VagrantPhpErrorLog();
        $this->detectors[] = new VagrantApacheErrorLog();
        $this->detectors[] = new VagrantApacheAccessLog();
        $this->detectors[] = new VagrantNginxErrorLog();
        $this->detectors[] = new VagrantNginxAccessLog();
        $this->detectors[] = new SticklerLog($databaseManager);
        $this->detectors[] = new DewdropActivityLog($databaseManager);
    }

    public function getAll()
    {
        return $this->detectors;
    }
}
