<?php
namespace Jibix\Replay\replay\action\type\entity;
use Jibix\Replay\entity\type\ReplayItemEntity;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\actor\ActorDespawnAction;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;


/**
 * Class EntitySpawnItemAction
 * @author Jibix
 * @date 25.12.2024 - 22:52
 * @project Replay
 */
class EntitySpawnItemAction extends EntityAction{

    protected const ID = ActionIds::ITEM_SPAWN;

    private Item $item;
    private Vector3 $position;

    public static function create(ItemEntity $entity): static{
        $action = new static();
        $action->entityId = $entity instanceof ReplayItemEntity ? $entity->getActualId() : $entity->getId();
        $action->item = $entity->getItem();
        $action->position = $entity->getPosition();
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putItem($this->item);
        $stream->putVector3($this->position);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->item = $stream->getItem();
        $this->position = $stream->getVector3();
    }

    public function handle(Replay $replay): void{
        $replay->spawnEntity(new ReplayItemEntity($this->entityId, Location::fromObject($this->position, $replay->getWorld()), $this->item));
    }

    public function handleReversed(Replay $replay): ?Action{
        return ActorDespawnAction::create($this->entityId);
    }
}