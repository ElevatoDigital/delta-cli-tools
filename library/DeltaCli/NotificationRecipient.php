<?php

namespace DeltaCli;

class NotificationRecipient
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $slackHandle;

    /**
     * Recipient constructor.
     * @param string|null $name
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function createRecipient()
    {
        return $this->project->createRecipient();
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $slackHandle
     * @return $this
     */
    public function setSlackHandle($slackHandle)
    {
        $this->slackHandle = ltrim($slackHandle, '@');

        return $this;
    }
}
