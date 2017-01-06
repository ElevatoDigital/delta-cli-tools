<?php

namespace DeltaCli;

use DeltaCli\FileTransferPaths\Path;

class FileTransferPaths
{
    const DOWN = 'download';

    const UP = 'upload';

    public function __construct(Project $project, $path1, $path2)
    {
        $path1 = new Path($project, $path1);
        $path2 = new Path($project, $path2);

        if ($path1->isLocal() && $path2->isLocal()) {
            // @todo throw exception about one path needing to be remote
        }

        if ($path1->isRemote()) {
            $this->localPath  = $path2;
            $this->remotePath = $path1;
            $this->direction  = self::DOWN;
        } else {
            $this->localPath  = $path1;
            $this->remotePath = $path2;
            $this->direction  = self::UP;
        }
    }

    public function getLocalPath()
    {
        return $this->localPath->getPath();
    }

    public function getRemotePath()
    {
        return $this->remotePath->getPath();
    }

    public function getRemoteEnvironment()
    {
        return $this->remotePath->getEnvironment();
    }

    public function getDirection()
    {
        return $this->direction;
    }
}
