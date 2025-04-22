<?php
namespace Jibix\Replay\entity\type;
use Jibix\Replay\entity\ReplayEntity;
use Jibix\Replay\entity\ReplayEntityTrait;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;


/**
 * Class ReplayHuman
 * @author Jibix
 * @date 26.12.2024 - 00:59
 * @project Replay
 */
class ReplayHuman extends Human implements ReplayEntity{
    use ReplayEntityTrait;

    public function __construct(
        private int $actualId,
        private bool $isPlayer,
        private EntitySizeInfo $sizeInfo,
        private Vector3 $offsetPosition,
        Location $location,
        Skin $skin,
        ?CompoundTag $nbt = null
    ){
        parent::__construct($location, $skin, $nbt);
        $this->setCanSaveWithChunk(false);
    }

    public function isPlayer(): bool{
        return $this->isPlayer;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return $this->sizeInfo;
    }

    public function getOffsetPosition(Vector3 $vector3): Vector3{
        return $this->offsetPosition->addVector($vector3);
    }

    public function getCustomNetworkTypeId(): string{
        return self::getNetworkTypeId();
    }
}