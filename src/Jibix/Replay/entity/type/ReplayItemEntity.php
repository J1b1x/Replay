<?php
namespace Jibix\Replay\entity\type;
use Jibix\Replay\entity\ReplayEntity;
use Jibix\Replay\entity\ReplayEntityTrait;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;


/**
 * Class ReplayItemEntity
 * @author Jibix
 * @date 26.12.2024 - 01:00
 * @project Replay
 */
class ReplayItemEntity extends ItemEntity implements ReplayEntity{
    use ReplayEntityTrait;

    public function __construct(
        private int $actualId,
        Location $location,
        Item $item,
        ?CompoundTag $nbt = null
    ){
        parent::__construct($location, $item, $nbt);
        $this->setCanSaveWithChunk(false);
    }

    public function getCustomNetworkTypeId(): string{
        return self::getNetworkTypeId();
    }
}