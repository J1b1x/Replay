<?php
namespace Jibix\Replay\replay\action\type\player;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\network\mcpe\protocol\AnimatePacket;


/**
 * Class PlayerAnimationAction
 * @author Jibix
 * @date 25.12.2024 - 22:43
 * @project Replay
 */
class PlayerAnimationAction extends EntityAction{

    protected const ID = ActionIds::PLAYER_ANIMATION;

    private int $animationType;

    public static function create(int $entityId, int $animationType): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->animationType = $animationType;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putInt($this->animationType);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->animationType = $stream->getInt();
    }

    public function handle(Replay $replay): void{
        $entity = $replay->getEntity($this->entityId);
        $replay->getWorld()->broadcastPacketToViewers($entity->getPosition(), AnimatePacket::create($entity->getId(), $this->animationType));
    }
}