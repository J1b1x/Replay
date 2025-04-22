<?php
namespace Jibix\Replay\replay\action\type\entity;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\TypeConverter;


/**
 * Class EntityChangeSkinAction
 * @author Jibix
 * @date 25.12.2024 - 22:47
 * @project Replay
 */
class EntityChangeSkinAction extends EntityAction{

    protected const ID = ActionIds::ENTITY_CHANGE_SKIN;

    private Skin $skin;

    public static function create(int $entityId, Skin $skin): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->skin = $skin;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putSkin(TypeConverter::getInstance()->getSkinAdapter()->toSkinData($this->skin));
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->skin = TypeConverter::getInstance()->getSkinAdapter()->fromSkinData($stream->getSkin());
    }

    public function handle(Replay $replay): void{
        if (!$entity = $replay->getEntity($this->entityId)) return; //don't ask me how tf this is even possible
        $entity->setSkin($this->skin);
        $entity->sendSkin();
    }

    public function handleReversed(Replay $replay): ?Action{
        return self::create($this->entityId, $replay->getEntity($this->entityId)->getSkin());
    }
}