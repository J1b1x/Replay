<?php
/**
 * Class ReplayStartEvent
 * @author Jibix
 * @date 21.04.2025 - 13:31
 * @project Replay
 */
namespace Jibix\Replay\replay\replayer\event;
use Jibix\Replay\replay\replayer\Replay;


class ReplayStartEvent extends ReplayEvent{

    public function __construct(private Replay $replay){}

    public function getReplay(): Replay{
        return $this->replay;
    }
}