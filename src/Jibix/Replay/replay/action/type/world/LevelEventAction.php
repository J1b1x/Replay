<?php
namespace Jibix\Replay\replay\action\type\world;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\WorldAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;


/**
 * Class LevelEventAction
 * @author Jibix
 * @date 25.12.2024 - 23:03
 * @project Replay
 */
class LevelEventAction extends WorldAction{

    protected const ID = ActionIds::LEVEL_EVENT;

    private int $eventId;
    private int $eventData;
    private ?Vector3 $position;

    public static function create(int $eventId, int $eventData, ?Vector3 $position): self{
        $action = new self();
        $action->eventId = $eventId;
        $action->eventData = $eventData;
        $action->position = $position;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        $stream->putVarInt($this->eventId);
        $stream->putVarInt($this->eventData);
        $stream->putVector3Nullable($this->position);
    }

    public function deserialize(BinaryStream $stream): void{
        $this->eventId = $stream->getVarInt();
        $this->eventData = $stream->getVarInt();
        $this->position = $stream->getVector3();
    }

    public function handle(Replay $replay): void{
        $replay->getWorld()->broadcastPacketToViewers($this->position, LevelEventPacket::create(
            $this->eventId,
            $this->eventData,
            $this->position
        ));
    }

    public function handleReversed(Replay $replay): ?Action{
        return in_array($this->eventId, $replay->getSettings()->getUnreversableLevelEventIds()) ? null : parent::handleReversed($replay);
    }
}