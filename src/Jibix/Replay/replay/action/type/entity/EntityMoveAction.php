<?php
namespace Jibix\Replay\replay\action\type\entity;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\entity\Location;
use pocketmine\world\Position;


/**
 * Class EntityMoveAction
 * @author Jibix
 * @date 25.12.2024 - 22:49
 * @project Replay
 */
class EntityMoveAction extends EntityAction{

    protected const ID = ActionIds::ENTITY_MOVE;

    private Location $location;
    private bool $teleport;

    public static function create(int $entityId, Location $location, bool $teleport): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->location = $location;
        $action->teleport = $teleport;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putLocation($this->location);
        $stream->putBool($this->teleport);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->location = $stream->getLocation();
        $this->teleport = $stream->getBool();
    }

    public function handle(Replay $replay): void{
        if (!$entity = $replay->getEntity($this->entityId)) return; //don't ask me how tf this is even possible
        if ($this->teleport) {
            $entity->teleport(Location::fromObject($this->location, $replay->getWorld(), $this->location->getYaw(), $this->location->getPitch()));
            return;
        }
        $entity->setPositionAndRotation(Position::fromObject($this->location, $replay->getWorld()), $this->location->getYaw(), $this->location->getPitch());
    }

    public function handleReversed(Replay $replay): ?Action{
        return self::create($this->entityId, $replay->getEntity($this->entityId)->getLocation(), $this->teleport);
    }
}