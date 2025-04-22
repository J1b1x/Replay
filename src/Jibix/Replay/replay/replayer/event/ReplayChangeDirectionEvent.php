<?php
/**
 * Class ReplayChangeDirectionEvent
 * @author Jibix
 * @date 21.04.2025 - 15:59
 * @project Replay
 */
namespace Jibix\Replay\replay\replayer\event;
use Jibix\Replay\replay\replayer\Replay;


class ReplayChangeDirectionEvent extends ReplayEvent{


    //If ANY mf asks my why there's no "newDirection" or "oldDirection" value or smth i'm gonna freak TF out, since there's just 2 directions,
    //and you can just get the new direction by using replay->getPlayDirection() SO OBVIOUSLY THE OLD ONE MUST BE THE OPPOSITE DIRECTION!!!??!?!?!?!
    public function __construct(private Replay $replay){}

    public function getReplay(): Replay{
        return $this->replay;
    }
}