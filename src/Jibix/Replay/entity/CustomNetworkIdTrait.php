<?php
namespace Jibix\Replay\entity;
use pocketmine\entity\Attribute;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\player\Player;


/**
 * Trait CustomNetworkIdTrait
 * @author Jibix
 * @date 26.12.2024 - 00:54
 * @project Replay
 */
trait CustomNetworkIdTrait{

    public function __construct(
        private string $networkTypeId,
        private int $actualId,
        private EntitySizeInfo $sizeInfo,
        private Vector3 $offsetPosition,
        Location $location,
        ?CompoundTag $nbt = null
    ){
        parent::__construct($location, $nbt);
        $this->setCanSaveWithChunk(false);
    }

    public static function getNetworkTypeId(): string{
        return EntityIds::XP_ORB;
    }

    protected function getInitialGravity(): float{
        return 0.08;
    }

    protected function getInitialDragMultiplier(): float{
        return 0.02;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return $this->sizeInfo;
    }

    public function getCustomNetworkTypeId(): string{
        return $this->networkTypeId;
    }

    public function getOffsetPosition(Vector3 $vector3): Vector3{
        return $this->offsetPosition->addVector($vector3);
    }

    protected function sendSpawnPacket(Player $player): void{
        $this->sendInternalSpawnPacket($player); //allows modification of the sendSpawnPacket function (like in ReplayLiving)
    }

    private function sendInternalSpawnPacket(Player $player): void{
        //Doing this to send the custom network id
        $player->getNetworkSession()->sendDataPacket(AddActorPacket::create(
            $this->getId(),
            $this->getId(),
            $this->networkTypeId,
            $this->location->asVector3(),
            $this->getMotion(),
            $this->location->pitch,
            $this->location->yaw,
            $this->location->yaw,
            $this->location->yaw,
            array_map(fn (Attribute $attribute): NetworkAttribute => new NetworkAttribute(
                $attribute->getId(),
                $attribute->getMinValue(),
                $attribute->getMaxValue(),
                $attribute->getValue(),
                $attribute->getDefaultValue(), []
            ), $this->attributeMap->getAll()),
            $this->getAllNetworkData(),
            new PropertySyncData([], []),
            []
        ));
    }
}