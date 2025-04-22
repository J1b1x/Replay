<?php
namespace Jibix\Replay\replay\action\type\entity;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\NetworkBroadcastUtils;


/**
 * Class EntityPlayEmoteAction
 * @author Jibix
 * @date 25.12.2024 - 22:50
 * @project Replay
 */
class EntityPlayEmoteAction extends EntityAction{

    protected const ID = ActionIds::ENTITY_PLAY_EMOTE;

    private string $emoteId;

    public static function create(int $entityId, string $emoteId): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->emoteId = $emoteId;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putString($this->emoteId);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->emoteId = $stream->getString();
    }

    public function handle(Replay $replay): void{
        if (!$entity = $replay->getEntity($this->entityId)) return; //don't ask me how tf this is even possible
        NetworkBroadcastUtils::broadcastEntityEvent($entity->getViewers(), fn (EntityEventBroadcaster $broadcaster, array $recipients) => $broadcaster->onEmote($recipients, $entity, $this->emoteId));
    }
}