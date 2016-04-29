<?php

namespace DeltaCli\Template;

use DeltaCli\Project;

interface TemplateInterface
{
    public function apply(Project $project);
}
