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
    }

    public function getAll()
    {
        return $this->detectors;
    }
}
