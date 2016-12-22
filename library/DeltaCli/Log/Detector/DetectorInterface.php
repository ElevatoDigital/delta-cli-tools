<?php

namespace DeltaCli\Log\Detector;

use DeltaCli\Host;

interface DetectorInterface
{
    public function detectLogOnHost(Host $host);
}
