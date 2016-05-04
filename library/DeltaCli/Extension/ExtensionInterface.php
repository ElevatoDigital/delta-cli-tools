<?php

namespace DeltaCli\Extension;

use DeltaCli\Project;

interface ExtensionInterface
{
    public function extend(Project $project);
}
