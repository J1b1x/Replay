<?php
namespace Jibix\Replay\replay\action\type\actor;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\entity\EntitySpawnAction;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;


/**
 * Class ActorDeathAction
 * @author Jibix
 * @date 25.12.2024 - 22:54
 * @project Replay
 */
class ActorDeathAction extends EntityAction{

    protected const ID = ActionIds::ACTOR_DEATH;

    public static function create(int $entityId): self{
        $action = new self();
        $action->entityId = $entityId;
        return $action;
    }

    public function handle(Replay $replay): void{
        $replay->getEntity($this->entityId)->kill();
    }

    public function handleReversed(Replay $replay): ?Action{
        return EntitySpawnAction::create($replay->getEntity($this->entityId));
    }
}