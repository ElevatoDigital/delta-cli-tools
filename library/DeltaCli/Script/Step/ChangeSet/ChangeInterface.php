<?php

namespace DeltaCli\Script\Step\ChangeSet;

interface ChangeInterface
{
    public function render();

    public function getSummaryTitle();
}
