<?php

namespace DeltaCli\Config\Detector;

class DetectorSet
{
    /**
     * @var DetectorInterface[]
     */
    private $detectors = [];

    public function __construct()
    {
        // $this->detectors[] = new WordPress();
        // $this->detectors[] = new Dewdrop();
        $this->detectors[] = new ZendFramework1();
    }

    public function getAll()
    {
        return $this->detectors;
    }
}
