<?php
namespace Jibix\Replay\replay\action\type\entity;
use Jibix\Replay\replay\action\Action;
use Jibix\Replay\replay\action\ActionIds;
use Jibix\Replay\replay\action\type\EntityAction;
use Jibix\Replay\replay\replayer\Replay;
use Jibix\Replay\util\BinaryStream;
use pocketmine\item\Item;


/**
 * Class EntityArmorEquipAction
 * @author Jibix
 * @date 25.12.2024 - 22:46
 * @project Replay
 */
class EntityArmorEquipAction extends EntityAction{

    protected const ID = ActionIds::ENTITY_ARMOR_EQUIP;

    /** @var Item[] */
    private array $contents;

    public static function create(int $entityId, array $contents): self{
        $action = new self();
        $action->entityId = $entityId;
        $action->contents = $contents;
        return $action;
    }

    public function serialize(BinaryStream $stream): void{
        parent::serialize($stream);
        $stream->putContents($this->contents);
    }

    public function deserialize(BinaryStream $stream): void{
        parent::deserialize($stream);
        $this->contents = $stream->getContents();
    }

    public function handle(Replay $replay): void{
        $replay->getEntity($this->entityId)?->getArmorInventory()->setContents($this->contents);
    }

    public function handleReversed(Replay $replay): ?Action{
        return self::create($this->entityId, $replay->getEntity($this->entityId)->getArmorInventory()->getContents());
    }
}