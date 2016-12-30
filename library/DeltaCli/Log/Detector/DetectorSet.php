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
        $this->detectors[] = new VagrantPhpErrorLog();
        $this->detectors[] = new VagrantApacheErrorLog();
        $this->detectors[] = new VagrantApacheAccessLog();
        $this->detectors[] = new VagrantNginxErrorLog();
        $this->detectors[] = new VagrantNginxAccessLog();
    }

    public function getAll()
    {
        return $this->detectors;
    }
}
