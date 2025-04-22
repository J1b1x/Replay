<?php
/**
 * Class ReplayTogglePauseEvent
 * @author Jibix
 * @date 21.04.2025 - 15:49
 * @project Replay
 */
namespace Jibix\Replay\replay\replayer\event;

use Jibix\Replay\replay\replayer\Replay;


class ReplayTogglePauseEvent extends ReplayEvent{

    public function __construct(private Replay $replay){}

    public function getReplay(): Replay{
        return $this->replay;
    }
}