<?php
namespace Jibix\Replay\replay\action\type\actor;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\network\mcpe\protocol\ActorEventPacket;


/**
 * Class ActorEventAction
 * @author Jibix
 * @date 25.12.2024 - 22:55
 * @project Replay
 */
class ActorEventAction extends EntityAction{

    protected const ID = ActionIds::ACTOR_EVENT;

    private int $eventId;
    private int $eventData;

    public static function create(int $entityId, int $eventId, int $eventData): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->eventId = $eventId;
        $action->eventData = $eventData;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putByte($this->eventId);
        $stream->putInt($this->eventData);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->eventId = $stream->getByte();
        $this->eventData = $stream->getInt();
    }

    public function handle(Replay $replay): void{
        if (!$entity = $replay->getEntity($this->entityId)) return; //don't ask me how tf this is even possible
        $replay->getWorld()->broadcastPacketToViewers($entity->getPosition(), ActorEventPacket::create($entity->getId(), $this->eventId, $this->eventData));
    }
}