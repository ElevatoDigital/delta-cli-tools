<?php

namespace DeltaCli\Script\Step;

use DeltaCli\Script\Step\ChangeSet\ChangeInterface;
use DeltaCli\Script\Step\ChangeSet\Delete;
use DeltaCli\Script\Step\ChangeSet\NewDir;
use DeltaCli\Script\Step\ChangeSet\NewFile;
use DeltaCli\Script\Step\ChangeSet\Update;

class ChangeSet
{
    private $changes = [];

    private $countsByType = [];

    public function newFile($file)
    {
        return $this->addChange(new NewFile($file));
    }

    public function newDirectory($file)
    {
        return $this->addChange(new NewDir($file));
    }

    public function update($file)
    {
        return $this->addChange(new Update($file));
    }

    public function delete($file)
    {
        return $this->addChange(new Delete($file));
    }

    public function getOutput()
    {
        if (10 >= count($this->changes)) {
            return $this->getVerboseOutput();
        } else {
            ksort($this->countsByType);

            $output = [];

            foreach ($this->countsByType as $title => $count) {
                if ($count) {
                    $output[] = sprintf('%s: %s', $title, $count);
                }
            }

            return $output;
        }
    }

    public function getVerboseOutput()
    {
        $output = [];

        foreach ($this->changes as $change) {
            $output[] = $change->render();
        }

        return $output;
    }

    private function addChange(ChangeInterface $change)
    {
        $this->changes[] = $change;

        if (!isset($this->countsByType[$change->getSummaryTitle()])) {
            $this->countsByType[$change->getSummaryTitle()] = 0;
        }

        $this->countsByType[$change->getSummaryTitle()] += 1;

        return $this;
    }
}
