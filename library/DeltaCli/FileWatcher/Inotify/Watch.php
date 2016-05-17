<?php

namespace DeltaCli\FileWatcher\Inotify;

class Watch
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @var array
     */
    private $watchIds;

    public function __construct(callable $callback, array $watchIds)
    {
        $this->callback = $callback;
        $this->watchIds = $watchIds;
    }

    /**
     * @param resource $inotify
     * @param array $paths
     * @param callable $callback
     * @return Watch
     */
    public static function factory($inotify, array $paths, callable $callback)
    {
        $ids = [];

        foreach ($paths as $path) {
            /** @noinspection PhpUndefinedFunctionInspection */
            /** @noinspection PhpUndefinedConstantInspection */
            $ids[] = inotify_add_watch(
                $inotify,
                $path,
                IN_CREATE | IN_MODIFY | IN_CLOSE_WRITE | IN_ATTRIB
            );
        }

        return new Watch($callback, $ids);
    }

    public function matchesEvent(array $event)
    {
        return in_array($event['wd'], $this->watchIds);
    }

    public function runCallback()
    {
        call_user_func($this->callback);
        return $this;
    }
}
