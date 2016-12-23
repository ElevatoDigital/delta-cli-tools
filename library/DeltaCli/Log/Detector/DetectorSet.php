<?php

namespace DeltaCli\Log\Detector;

class DetectorSet
{
    /**
     * @var DetectorInterface[]
     */
    private $detectors = [];

    public function __construct()
    {
        $this->detectors[] = new ApacheErrorLog();
        $this->detectors[] = new ApacheAccessLog();
        $this->detectors[] = new DewdropMonolog();
        $this->detectors[] = new NginxAccessLog();
        $this->detectors[] = new NginxErrorLog();
    }

    public function getAll()
    {
        return $this->detectors;
    }
}
