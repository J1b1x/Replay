<?php
namespace Jibix\Replay\replay\action\type\actor;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;


/**
 * Class ActorSetMetadataAction
 * @author Jibix
 * @date 25.12.2024 - 22:57
 * @project Replay
 */
class ActorSetMetadataAction extends EntityAction{

    protected const ID = ActionIds::SET_METADATA;

    /** @var MetadataProperty[] */
    private array $metadata;

    public static function create(int $entityId, array $metadata): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->metadata = $metadata;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putEntityMetadata($this->metadata);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->metadata = $stream->getEntityMetadata();
    }

    public function handle(Replay $replay): void{
        if (!$entity = $replay->getEntity($this->entityId)) return; //don't ask me how tf this is even possible
        $properties = $entity->getNetworkProperties();
        foreach ($this->metadata as $key => $metadata) {
            $properties->set($key, $metadata);
        }
    }

    public function handleReversed(Replay $replay): ?Action{
        if (!$entity = $replay->getEntity($this->entityId)) return null; //don't ask me how tf this is even possible
        return self::create($this->entityId, $entity->getNetworkProperties()->getAll());
    }
}