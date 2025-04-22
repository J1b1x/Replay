<?php
namespace Jibix\Replay\replay\action\type\actor;
use Jibix\Replay\entity\type\ReplayItemEntity;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\entity\EntitySpawnAction;
use Jibix\Replay\replay\action\type\entity\EntitySpawnItemAction;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;


/**
 * Class ActorDespawnAction
 * @author Jibix
 * @date 25.12.2024 - 22:54
 * @project Replay
 */
class ActorDespawnAction extends EntityAction{

    protected const ID = ActionIds::ACTOR_DESPAWN;

    public static function create(int $entityId): self{
        $action = new self();
        $action->entityId = $entityId;
        return $action;
    }

    public function handle(Replay $replay): void{
        $replay->despawnEntity($this->entityId);
    }

    public function handleReversed(Replay $replay): ?Action{
        $entity = $replay->getEntity($this->entityId);
        if ($entity === null || !$entity->isAlive()) return null;
        return $entity instanceof ReplayItemEntity ? EntitySpawnItemAction::create($entity) : EntitySpawnAction::create($entity);
    }
}