<?php
/**
 * Class ReplayRestartEvent
 * @author Jibix
 * @date 21.04.2025 - 13:36
 * @project Replay
 */
namespace Jibix\Replay\replay\replayer\event;
use Jibix\Replay\replay\replayer\Replay;


class ReplayRestartEvent extends ReplayEvent{

    public function __construct(
        private Replay $replay,
        private bool $isReversed = false
    ){}

    public function getReplay(): Replay{
        return $this->replay;
    }

    public function isReversed(): bool{
        return $this->isReversed;
    }
}