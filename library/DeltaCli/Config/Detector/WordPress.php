<?php

namespace DeltaCli\Config\Detector;

class WordPress
{
    public function getPotentialFilePaths()
    {
        return [
            'httpdocs/wp-config.php',
            'wp-config.php',
            'src/wp-config.php'
        ];
    }
}
